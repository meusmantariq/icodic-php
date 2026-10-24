<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Request, Response, Csrf, Config, Database as DB};
use App\Models\Repo;

/** Replaces Core.InvokeLLM: OpenAI-compatible API if configured, otherwise a catalog-grounded offline assistant. */
final class ChatController
{
    public function message(Request $req): never
    {
        Csrf::check($req);
        $msg = mb_substr($req->str('message'), 0, 1000);
        if ($msg === '') Response::json(['ok' => false, 'error' => 'Empty message'], 422);
        $sid = session_id();
        $recent = (int)DB::scalar('SELECT COUNT(*) FROM chat_logs WHERE session_id = ? AND role = "user" AND created_at > (NOW() - INTERVAL 1 MINUTE)', [$sid]);
        if ($recent > 15) Response::json(['ok' => false, 'error' => 'Too many messages, please slow down.'], 429);
        DB::query('INSERT INTO chat_logs (session_id, role, content) VALUES (?, "user", ?)', [$sid, $msg]);
        $history = array_reverse(DB::fetchAll('SELECT role, content FROM chat_logs WHERE session_id = ? ORDER BY id DESC LIMIT 12', [$sid]));
        $reply = Config::get('chat.api_key') ? $this->llm($history) : null;
        $reply ??= $this->offline($msg);
        DB::query('INSERT INTO chat_logs (session_id, role, content) VALUES (?, "assistant", ?)', [$sid, $reply]);
        Response::json(['ok' => true, 'reply' => $reply]);
    }

    private function context(): string
    {
        $byCat = [];
        foreach (Repo::all('software_products', 'name ASC') as $p) $byCat[$p['category']][] = $p['name'] . ' ($' . $p['price_monthly'] . '/mo, $' . $p['price_yearly'] . '/yr USD)';
        $cat = implode("\n", array_map(fn($c, $n) => "- $c: " . implode(', ', $n), array_keys($byCat), $byCat));
        $erp = implode(', ', array_column(Repo::all('erp_solutions', 'sort_order ASC'), 'name'));
        $srv = implode(', ', array_column(Repo::all('services', 'sort_order ASC'), 'title'));
        $cyb = implode(', ', array_column(Repo::all('cyber_services', 'sort_order ASC'), 'title'));
        $wa = Repo::settings()['whatsapp_number'] ?? Config::get('site.whatsapp_number');
        return "You are the AI assistant for Quantum Nexis, a global IT company operating across the UK, UAE, India and Pakistan, offering enterprise software licensing, ERP implementation, cyber security and IT services.\nSoftware catalog by category (USD list prices):\n$cat\nERP solutions: $erp.\nIT services: $srv.\nCyber security services: $cyb.\nRegions: UK (GBP), UAE (AED), India (INR), Pakistan (PKR). Prices convert by region.\nAnswer concisely and helpfully based on this site. Use short paragraphs or bullet points. For purchases or consultations, direct users to the contact page or WhatsApp +$wa. Do not invent prices not in the catalog.";
    }

    private function llm(array $history): ?string
    {
        $messages = [['role' => 'system', 'content' => $this->context()]];
        foreach ($history as $h) $messages[] = ['role' => $h['role'], 'content' => $h['content']];
        $ch = curl_init(rtrim(Config::get('chat.base_url', 'https://api.openai.com/v1'), '/') . '/chat/completions');
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 40,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . Config::get('chat.api_key')],
            CURLOPT_POSTFIELDS => json_encode(['model' => Config::get('chat.model', 'gpt-4o-mini'), 'messages' => $messages, 'max_tokens' => 500, 'temperature' => 0.4])]);
        $res = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if (!$res || $code >= 400) { error_log("Chat LLM error $code: $res"); return null; }
        $j = json_decode($res, true);
        return trim((string)($j['choices'][0]['message']['content'] ?? '')) ?: null;
    }

    /** Keyword assistant grounded in the database — works with no API key. */
    private function offline(string $q): string
    {
        $ql = mb_strtolower($q);
        $wa = '+' . (Repo::settings()['whatsapp_number'] ?? Config::get('site.whatsapp_number'));
        $hits = [];
        foreach (Repo::all('software_products', 'name ASC') as $p) {
            $n = mb_strtolower($p['name']);
            foreach (preg_split('/\W+/u', $ql) as $w) if (mb_strlen($w) >= 4 && (str_contains($n, $w) || str_contains(mb_strtolower($p['brand']), $w))) { $hits[$p['id']] = $p; break; }
        }
        if ($hits) {
            $lines = array_map(fn($p) => "• {$p['name']} ({$p['brand']}, {$p['category']}): " . format_price($p['price_monthly']) . "/mo or " . format_price($p['price_yearly']) . "/yr" . ($p['in_stock'] ? '' : ' — on request'), array_slice(array_values($hits), 0, 6));
            return "Here is what I found in our catalog:\n" . implode("\n", $lines) . "\n\nUse “Inquire Now” on any product to book, or WhatsApp us at $wa.";
        }
        if (preg_match('/erp|odoo|dynamics|crm/', $ql)) {
            $s = array_map(fn($r) => "• {$r['name']} — {$r['tagline']}", Repo::all('erp_solutions', 'sort_order ASC'));
            return "Our ERP practice covers:\n" . implode("\n", $s) . "\n\nEvery ERP engagement starts with a free consultation — use the Contact page or WhatsApp $wa.";
        }
        if (preg_match('/cyber|security|firewall|soc|pentest|penetration|compliance/', $ql)) {
            $s = array_map(fn($r) => "• {$r['title']}: {$r['description']}", Repo::all('cyber_services', 'sort_order ASC'));
            return "Yes — our managed cyber security practice serves the UK, UAE, India and Pakistan:\n" . implode("\n", $s) . "\n\nRequest a free assessment on the Cyber Security page.";
        }
        if (preg_match('/service|support|network|hardware|cloud|licen/', $ql)) {
            $s = array_map(fn($r) => "• {$r['title']}", Repo::all('services', 'sort_order ASC'));
            return "Our IT services include:\n" . implode("\n", $s) . "\n\nSee the Services page for details or contact a regional office.";
        }
        if (preg_match('/pakistan|india|uae|dubai|uk|london|karachi|office|region|where/', $ql)) {
            $s = array_map(fn($o) => "• {$o['city']}, {$o['name']}: {$o['phone']} · {$o['email']}", \App\Core\Region::all());
            return "We operate from four regional offices:\n" . implode("\n", $s);
        }
        if (preg_match('/price|cost|how much|quote/', $ql)) return "Prices are shown per seat on the Software catalog and convert to your region's currency (GBP, AED, INR, PKR). Tell me the product name and I'll look it up, or WhatsApp $wa for a formal quote.";
        if (preg_match('/track|order status|order id/', $ql)) return "You can track any order with its ID (format IC-XXXXXX) on the Track Order page.";
        return "I can help with our software catalog, ERP solutions (ERPNext, Odoo, Custom CRM/ERP, Dynamics 365), cyber security and IT services across the UK, UAE, India and Pakistan. Try asking about a product name, or contact us on WhatsApp at $wa.";
    }
}
