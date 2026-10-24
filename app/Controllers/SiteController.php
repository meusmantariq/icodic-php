<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Request, Response, View, Csrf, Mailer, Region, I18n, Session};
use App\Models\{Repo, Order, PageBlock};

final class SiteController
{
    public function home(Request $req): string
    {
        $region = Region::code();
        $products = array_filter(Repo::all('software_products', 'created_at DESC'), function ($p) use ($region) {
            $r = json_list($p['regions']); return !$r || in_array($region, $r, true);
        });
        return View::page('pages.home', [
            'title' => null,
            'products' => array_values($products),
            'solutions' => Repo::all('erp_solutions', 'sort_order ASC'),
            'projects' => Repo::all('projects', 'created_at DESC', 30),
            'cyber' => Repo::all('cyber_services', 'sort_order ASC'),
            'services' => Repo::all('services', 'sort_order ASC'),
            'testimonials' => Repo::all('testimonials', 'sort_order ASC'),
            'feed' => Repo::all('deployment_feed', 'sort_order ASC', 50),
        ]);
    }
    public function about(Request $req): string { return View::page('pages.about', ['title' => 'About']); }
    public function services(Request $req): string { return View::page('pages.services', ['title' => 'Services', 'services' => Repo::all('services', 'sort_order ASC')]); }
    public function projects(Request $req): string { return View::page('pages.projects', ['title' => 'Projects', 'projects' => Repo::all('projects', 'created_at DESC', 100)]); }
    public function cyber(Request $req): string { return View::page('pages.cyber', ['title' => 'Cyber Security', 'services' => Repo::all('cyber_services', 'sort_order ASC')]); }
    public function contact(Request $req): string { return View::page('pages.contact', ['title' => 'Contact']); }
    public function trackOrder(Request $req): string { return View::page('pages.track', ['title' => 'Track Order']); }

    public function solution(Request $req, string $slug): string
    {
        $content = require APP_PATH . '/config/solutions.php';
        if (!isset($content[$slug])) Response::notFound('Solution not found.');
        $c = $content[$slug];
        $db = Repo::findBy('erp_solutions', 'type', $c['type']);
        return View::page('pages.solution', ['title' => $c['name'], 'c' => $c, 'db' => $db]);
    }
    public function erpDetail(Request $req, string $id): string
    {
        $sol = ctype_digit($id) ? Repo::find('erp_solutions', (int)$id) : Repo::findBy('erp_solutions', 'legacy_id', $id);
        if (!$sol) Response::notFound('Solution not found.');
        return View::page('pages.erp_detail', ['title' => $sol['name'], 'sol' => $sol]);
    }

    /** Language / region / billing-period switcher (cookie based). */
    public function prefs(Request $req): never
    {
        if ($l = $req->str('lang')) I18n::set($l);
        if ($r = $req->str('region')) Region::set($r);
        if ($p = $req->str('period')) Region::setPeriod($p);
        $back = $req->str('back', '/');
        if (!str_starts_with($back, '/') || str_starts_with($back, '//')) $back = '/';
        if ($req->wantsJson()) Response::json(['ok' => true]);
        Response::redirect($back);
    }

    // ---------- Forms ----------
    private function honeypot(Request $req): bool { return $req->str('website') !== ''; }

    public function submitContact(Request $req): never
    {
        Csrf::check($req);
        $data = ['name' => $req->str('name'), 'email' => strtolower($req->str('email')), 'company' => $req->str('company'), 'message' => $req->str('message'), 'region' => Region::code(), 'ip_address' => $req->ip()];
        if ($this->honeypot($req)) Response::redirect('/contact?sent=1');
        $errors = [];
        if (mb_strlen($data['name']) < 2) $errors[] = 'Please enter your name.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
        if (mb_strlen($data['message']) < 3) $errors[] = 'Please enter a message.';
        if ($errors) { Session::flash('errors', $errors); Session::set('_old', $data); Response::redirect('/contact#form'); }
        Repo::insert('contact_messages', $data);
        Session::forget('_old');
        Mailer::notifyAdmin("New contact message from {$data['name']}", "Name: {$data['name']}\nEmail: {$data['email']}\nCompany: {$data['company']}\nRegion: {$data['region']}\n\n{$data['message']}\n\nManage in Admin → Messages.");
        Response::redirect('/contact?sent=1#form');
    }

    public function submitAssessment(Request $req): never
    {
        Csrf::check($req);
        if ($this->honeypot($req)) Response::redirect('/cyber-security?sent=1#assessment');
        $opts = ['Endpoint Protection', 'Network Security', 'SOC & SIEM', 'Penetration Testing', 'Cloud Security', 'Compliance & GRC', 'General Assessment'];
        $data = [
            'name' => $req->str('name'), 'email' => strtolower($req->str('email')), 'company' => $req->str('company'),
            'region' => in_array($req->str('region'), ['Canada', 'UAE', 'India', 'Pakistan', 'Global'], true) ? $req->str('region') : 'UAE',
            'service_needed' => in_array($req->str('service_needed'), $opts, true) ? $req->str('service_needed') : 'General Assessment',
            'urgency' => in_array($req->str('urgency'), ['Low', 'Medium', 'High', 'Critical'], true) ? $req->str('urgency') : 'Medium',
            'message' => $req->str('message'), 'ip_address' => $req->ip(),
        ];
        $errors = [];
        if (mb_strlen($data['name']) < 2) $errors[] = 'Please enter your name.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid work email.';
        if (mb_strlen($data['message']) < 3) $errors[] = 'Please describe your needs.';
        if ($errors) { Session::flash('errors', $errors); Session::set('_old', $data); Response::redirect('/cyber-security#assessment'); }
        Repo::insert('security_assessments', $data);
        Session::forget('_old');
        Mailer::notifyAdmin("Security assessment request ({$data['urgency']}) — {$data['name']}", "Name: {$data['name']}\nEmail: {$data['email']}\nCompany: {$data['company']}\nRegion: {$data['region']}\nService: {$data['service_needed']}\nUrgency: {$data['urgency']}\n\n{$data['message']}");
        Response::redirect('/cyber-security?sent=1#assessment');
    }

    /** AJAX: create an order from the product modal (replaces OrderModal → base44.entities.Order.create + SendEmail). */
    public function submitOrder(Request $req): never
    {
        Csrf::check($req);
        if ($this->honeypot($req)) Response::json(['ok' => true, 'order_id' => 'IC-SPAM00']);
        $product = Repo::find('software_products', $req->int('product_id'));
        if (!$product) Response::json(['ok' => false, 'error' => 'Product not found.'], 404);
        $name = $req->str('customer_name'); $email = strtolower($req->str('email'));
        if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) Response::json(['ok' => false, 'error' => 'Please enter your name and a valid email to book.'], 422);
        $qty = max(1, min(100000, $req->int('quantity', 1)));
        $period = $req->str('billing_period') === 'yearly' ? 'yearly' : 'monthly';
        $price = (float)($period === 'monthly' ? $product['price_monthly'] : $product['price_yearly']);
        $total = round($price * $qty, 2);
        $orderId = Order::generateId();
        $region = Region::code();
        Repo::insert('orders', [
            'order_id' => $orderId, 'customer_name' => $name, 'email' => $email, 'phone' => $req->str('phone'), 'company' => $req->str('company'), 'region' => $region,
            'product_id' => (int)$product['id'], 'product_legacy_id' => $product['legacy_id'], 'product_name' => $product['name'], 'quantity' => $qty, 'billing_period' => $period,
            'notes' => $req->str('notes'), 'total_usd' => $total, 'status' => 'new', 'ip_address' => $req->ip(),
        ]);
        $summary = "Order ID: $orderId\nProduct: {$product['name']} ({$product['brand']})\nBilling: $period\nQuantity: $qty\nTotal (USD): \$$total\n\nCustomer: $name\nEmail: $email\nPhone: {$req->str('phone')}\nCompany: {$req->str('company')}\nRegion: $region\nNotes: " . ($req->str('notes') ?: '-');
        Mailer::notifyAdmin("New Order $orderId — {$product['name']}", "New order received.\n\n$summary\n\nManage in Admin → Orders (CRM).");
        Mailer::send($email, "Your Quantum Nexis order $orderId", "Thank you, $name.\n\nYour order has been received and our team will contact you shortly.\n\n$summary\n\nTrack your order any time: " . config('app.url') . url('/track-order') . "\nWhatsApp: +" . (Repo::settings()['whatsapp_number'] ?? config('site.whatsapp_number')));
        Response::json(['ok' => true, 'order_id' => $orderId, 'total' => format_price($total), 'email' => $email]);
    }

    /** Replaces the trackOrder backend function. */
    public function submitTrack(Request $req): never
    {
        Csrf::check($req);
        $id = $req->str('order_id');
        if ($id === '') Response::json(['found' => false, 'error' => 'Order ID is required'], 400);
        $o = Order::track($id);
        if (!$o) Response::json(['found' => false]);
        Response::json(['found' => true, 'order' => $o]);
    }
}
