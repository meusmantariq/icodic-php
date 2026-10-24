<?php
use App\Core\Region;
$site = App\Models\Repo::settings(); $r = Region::current(); $regionCode = Region::code(); $period = Region::period();
$heroTitle = $site['hero_title'] ?: t('hero.title');
$feedItems = $feed ?: [['region' => 'UAE', 'item' => 'Adobe Creative Cloud · 240 seats', 'action' => 'deployed'], ['region' => 'UK', 'item' => 'ERPNext · Enterprise', 'action' => 'implemented'], ['region' => 'IN', 'item' => 'AutoCAD · 85 licenses', 'action' => 'renewed'], ['region' => 'PK', 'item' => 'Odoo · Custom Module', 'action' => 'deployed'], ['region' => 'UAE', 'item' => 'MS Office 365 · 1,200 seats', 'action' => 'provisioned'], ['region' => 'UK', 'item' => 'Custom CRM', 'action' => 'launched']];
$caps = [['Custom ERP', 'Boxes', 'c-blue'], ['Custom CRM', 'Layers', 'c-violet'], ['AI Systems', 'Cpu', 'c-emerald'], ['Software Licensing', 'Package', 'c-amber']];
$stats = [['4', 'Global Regions', 'linear-gradient(90deg,var(--primary),var(--cobalt))'], ['500+', 'Enterprise Deployments', 'linear-gradient(90deg,var(--mint),#10b981)'], ['11', 'Years Combined', 'linear-gradient(90deg,var(--cobalt),var(--primary))']];
$back = urlencode('/');
?>
<section class="hero">
  <div class="ghost-grid"></div>
  <div class="blob" style="left:-5rem;top:2.5rem;width:16rem;height:16rem;background:hsl(243 75% 59%/.1)"></div><div class="blob" style="right:-2.5rem;bottom:0;width:18rem;height:18rem;background:hsl(158 64% 40%/.1)"></div>
  <div class="container hero-grid">
    <div class="fade-up">
      <div class="status-pill"><span class="dot"></span> SYSTEM ACTIVE · <?= e($r['city']) ?> <span data-tz="<?= e($r['tz']) ?>"><?= e(local_time($r['tz'])) ?></span></div>
      <h1 class="h1"><span data-typewriter="<?= e($heroTitle) ?>"><?= e($heroTitle) ?></span></h1>
      <p class="lead" style="max-width:36rem;margin-top:1.5rem"><?= e(t('hero.subtitle')) ?></p>
      <div class="flex wrap gap-2 mt-6"><?php foreach ($caps as [$l, $i, $c]): ?><span class="chip <?= $c ?>"><?= icon($i) ?> <?= e($l) ?></span><?php endforeach; ?></div>
      <form class="search" id="hero-form" action="<?= url('/#software') ?>"><div class="search-wrap"><?= icon('Search') ?><input id="hero-search" type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(t('hero.search')) ?>"></div><button class="btn btn-primary" type="submit"><?= e(t('hero.searchBtn')) ?> <?= icon('ArrowRight') ?></button></form>
      <div class="stats"><?php foreach ($stats as [$k, $l, $bar]): ?><div class="stat"><div class="stat-bar" style="background:<?= $bar ?>"></div><div class="stat-k"><?= e($k) ?></div><div class="stat-l"><?= e($l) ?></div></div><?php endforeach; ?></div>
    </div>
    <div class="feed">
      <div class="feed-head"><div class="flex items-center gap-2 mono xs upper"><?= icon('Activity', 'ico mint') ?> Live Deployment Feed</div><span class="mono xs mint">● STREAMING</span></div>
      <div class="feed-body"><?php foreach ($feedItems as $f): ?><div class="feed-row"><span class="feed-badge"><?= e($f['region']) ?></span><div style="min-width:0;flex:1"><div class="truncate small"><?= e($f['item']) ?></div><div class="mono xs upper muted"><?= e($f['action']) ?></div></div><span class="dot"></span></div><?php endforeach; ?></div>
      <div class="feed-foot">Real-time enterprise license &amp; ERP deployments across the iCodic network.</div>
    </div>
  </div>
</section>

<?php $brands = ['Odoo' => ['odoo.com', 'Boxes'], 'TallyPrime' => ['tallysolutions.com', 'Calculator'], 'Zoho' => ['zoho.com', 'Grid'], 'ERPNext' => ['erpnext.com', 'Boxes'], '3CX' => ['3cx.com', 'Phone'], 'Intuit QuickBooks' => ['quickbooks.intuit.com', 'Wallet'], 'Sage' => ['sage.com', 'Calculator'], 'Fortinet' => ['fortinet.com', 'ShieldCheck'], 'Elate CRM' => [null, 'Layers'], 'Elate HRMS' => [null, 'Layers'], 'Elate ERP' => [null, 'Layers'], 'Cheque Printing' => [null, 'Printer'], 'Property Management' => [null, 'Building2'], 'Time Management' => [null, 'Clock']];
$orbits = [[47, 60, 1, ['Elate CRM', 'Odoo', 'TallyPrime', 'Cheque Printing', 'Zoho', 'Elate HRMS', 'Property Management']], [33, 42, -1, ['Time Management', 'Elate ERP', 'ERPNext']], [18, 26, 1, ['3CX', 'Intuit QuickBooks', 'Sage', 'Fortinet']]];
$rings = ['hsl(243 75% 59%/.25)', 'hsl(226 71% 55%/.25)', 'hsl(158 64% 40%/.3)']; ?>
<section id="orbit" class="section section-sec">
  <div class="container">
    <div class="reveal sec-head center"><div class="eyebrow">// 01 — Ecosystem</div><h2 class="h2"><?= e(t('orbit.title')) ?></h2><p class="lead"><?= e(t('orbit.subtitle')) ?></p></div>
    <div class="reveal orbit-wrap">
      <?php foreach ($orbits as $i => [$rad, $dur, $dir, $items]): ?><div class="ring" style="width:<?= $rad * 2 ?>%;height:<?= $rad * 2 ?>%;border-color:<?= $rings[$i] ?>"></div><?php endforeach; ?>
      <div class="orbit-core"><?php if (!empty($site['company_logo_url'])): ?><img src="<?= e(img($site['company_logo_url'])) ?>" alt="<?= e($site['company_name'] ?? 'iCodic') ?>"><?php endif; ?><b><?= e($site['company_name'] ?? 'iCodic') ?></b><span>Command Center</span></div>
      <?php foreach ($orbits as [$rad, $dur, $dir, $items]): ?><div class="orbit-track" data-radius="<?= $rad ?>" data-duration="<?= $dur ?>" data-dir="<?= $dir ?>">
        <?php foreach ($items as $label): [$fav, $ic] = $brands[$label]; ?><div class="orbit-node" title="<?= e($label) ?>"><?php if ($fav): ?><img src="https://www.google.com/s2/favicons?domain=<?= e($fav) ?>&sz=128" alt="<?= e($label) ?>" loading="lazy" onerror="this.outerHTML='<?= str_replace(['"', "'"], ['&quot;', ''], icon($ic, 'ico text-primary')) ?>'"><?php else: ?><?= icon($ic, 'ico text-primary') ?><?php endif; ?></div><?php endforeach; ?>
      </div><?php endforeach; ?>
    </div>
    <div class="orbit-legend reveal"><?php foreach ($brands as $label => $b): ?><span class="tag"><?= e($label) ?></span><?php endforeach; ?></div>
  </div>
</section>

<?php $cats = ['Design', 'Productivity', 'Office Tools', 'CRM', 'ERP', 'Management', 'Security', 'AI Systems', 'Other'];
$byCat = []; foreach ($products as $p) $byCat[$p['category']][] = $p;
$firstCat = null; foreach ($cats as $c) if (!empty($byCat[$c])) { $firstCat = $c; break; }
$cur = Region::CURRENCIES[$regionCode] ?? ['symbol' => '$', 'rate' => 1]; ?>
<section id="software" class="section section-grad">
  <div class="container">
    <div class="reveal sec-head">
      <div class="flex wrap justify-between gap-4" style="align-items:flex-end">
        <div><div class="eyebrow primary">// 02 — License Matrix</div><h2 class="h2"><?= e(t('software.title')) ?></h2><p class="lead"><?= e(t('software.pricing')) ?> <span class="mint"><?= e(t('software.' . $period)) ?></span>.</p></div>
        <div class="toggle"><a class="<?= $period === 'monthly' ? 'active' : '' ?>" href="<?= url('/prefs?period=monthly&back=' . urlencode('/#software')) ?>"><?= e(t('software.monthly')) ?></a><a class="<?= $period === 'yearly' ? 'active' : '' ?>" href="<?= url('/prefs?period=yearly&back=' . urlencode('/#software')) ?>"><?= e(t('software.yearly')) ?></a></div>
      </div>
      <div class="cats only-desktop"><?php foreach ($cats as $c): if (empty($byCat[$c])) continue; ?><button type="button" data-cat="<?= e($c) ?>" class="<?= $c === $firstCat ? 'active' : '' ?>"><?= e($c) ?> <span class="muted">(<?= count($byCat[$c]) ?>)</span></button><?php endforeach; ?></div>
    </div>
    <?php if (!$products): ?><div class="empty mt-8"><?= e(t('software.none')) ?></div><?php else: ?>
    <div class="cat-empty empty mt-8" hidden><?= e(t('software.none')) ?></div>
    <div class="grid lg-3 mt-8 cat-grid only-desktop"><?php foreach ($products as $p) echo App\Core\View::render('sections.product_card', ['p' => $p, 'period' => $period, 'regionCode' => $regionCode]); ?></div>
    <div class="only-mobile mt-6"><?php $first = true; foreach ($cats as $c): if (empty($byCat[$c])) continue; ?><details class="acc" <?= $first ? 'open' : '' ?>><summary><?= e($c) ?> <span class="muted acc-n">(<?= count($byCat[$c]) ?>)</span></summary><div class="acc-body"><?php foreach ($byCat[$c] as $p) echo App\Core\View::render('sections.product_card', ['p' => $p, 'period' => $period, 'regionCode' => $regionCode]); ?></div></details><?php $first = false; endforeach; ?></div>
    <?php endif; ?>
  </div>
</section>

<?php $slug = ['ERPNext' => 'erpnext', 'Odoo' => 'odoo', 'Custom CRM' => 'custom-crm', 'Custom ERP' => 'custom-erp']; ?>
<section id="erp" class="section" style="position:relative;overflow:hidden">
  <div class="ghost-grid" style="opacity:.15"></div>
  <div class="container" style="position:relative">
    <div class="reveal sec-head"><div class="eyebrow">// 03 — The ERP Bridge</div><h2 class="h2"><?= e(t('erp.title')) ?></h2><p class="lead"><?= e(t('erp.subtitle')) ?></p></div>
    <div class="grid lg-2 mt-8"><?php foreach ($solutions as $s): $to = isset($slug[$s['type']]) ? '/solutions/' . $slug[$s['type']] : '/erp/' . $s['id']; ?>
      <div class="sol reveal card-hover">
        <?php if ($s['image_url']): ?><div class="sol-img"><img src="<?= e(img($s['image_url'])) ?>" alt="<?= e($s['name']) ?>" loading="lazy"></div><?php endif; ?>
        <div class="sol-body">
          <div class="flex wrap gap-2"><span class="tag tag-primary"><?= e($s['type']) ?></span><?php if ($s['consultation']): ?><span class="tag tag-mint">Consultation required</span><?php endif; ?></div>
          <h3><?= e($s['name']) ?></h3><?php if ($s['tagline']): ?><p class="mt-1 mint mono small"><?= e($s['tagline']) ?></p><?php endif; ?>
          <p class="mt-3 muted small"><?= e($s['description']) ?></p>
          <?php $feats = json_list($s['features']); if ($feats): ?><ul class="features"><?php foreach (array_slice($feats, 0, 6) as $f): ?><li><?= icon('Check') ?> <?= e($f) ?></li><?php endforeach; ?></ul><?php endif; ?>
          <div class="flex wrap gap-3 mt-6" style="margin-top:auto;padding-top:1.5rem"><a class="btn btn-primary btn-sm" href="<?= url($to) ?>"><?= e(t('cta.details')) ?> <?= icon('ArrowUpRight') ?></a><a class="btn btn-mint btn-sm" href="<?= url('/contact') ?>"><?= e(t('cta.consult')) ?> <?= icon('ArrowUpRight') ?></a></div>
        </div>
      </div><?php endforeach; ?></div>
  </div>
</section>

<section id="projects" class="section section-sec">
  <div class="container">
    <div class="reveal sec-head flex wrap justify-between gap-4" style="align-items:flex-end">
      <div><div class="eyebrow">// 04 — Project Archive</div><h2 class="h2"><?= e(t('projects.title')) ?></h2></div>
      <div class="flex items-center gap-3"><a class="btn-link" href="<?= url('/projects') ?>"><?= e(t('projects.viewall')) ?> <?= icon('ArrowUpRight') ?></a><div class="flex gap-1"><button class="sq" type="button" data-scroll="-380" data-target="#proj-scroller" aria-label="scroll left"><?= icon('ChevronLeft') ?></button><button class="sq" type="button" data-scroll="380" data-target="#proj-scroller" aria-label="scroll right"><?= icon('ChevronRight') ?></button></div></div>
    </div>
  </div>
  <?php if (!$projects): ?><div class="container"><div class="empty mt-8">Project case studies will appear here.</div></div><?php else: ?>
  <div class="scroller" id="proj-scroller" style="padding-left:max(1rem,calc((100vw - var(--max))/2 + 2rem))"><?php foreach ($projects as $p) echo App\Core\View::render('sections.project_card', ['p' => $p, 'site' => $site]); ?></div>
  <?php endif; ?>
</section>

<?php $chips = ['c-red', 'c-blue', 'c-emerald', 'c-amber', 'c-violet', 'c-cyan']; ?>
<section id="cyber" class="section">
  <div class="container">
    <div class="reveal sec-head"><div class="eyebrow">// 05 — Cyber Security</div><h2 class="h2"><?= e(t('cyber.title')) ?></h2><p class="lead"><?= e(t('cyber.subtitle')) ?></p></div>
    <?php if (!$cyber): ?><div class="empty mt-8">No cyber security services yet. Add them from the admin panel.</div><?php else: ?>
    <div class="grid sm-2 lg-3 mt-8"><?php foreach ($cyber as $i => $s): ?><div class="svc reveal card-hover"><?php if ($s['image_url']): ?><div class="svc-img"><img src="<?= e(img($s['image_url'])) ?>" alt="<?= e($s['title']) ?>" loading="lazy"></div><?php endif; ?><div class="icon-circle <?= $chips[$i % 6] ?>"><?= icon($s['icon']) ?></div><h3><?= e($s['title']) ?></h3><p><?= e($s['description']) ?></p></div><?php endforeach; ?></div><?php endif; ?>
    <div class="cta-band cta-dark reveal"><div><h3>Need a security assessment?</h3><p>Book a consultation with our cyber security team across UK, UAE, IN &amp; PK.</p></div><a class="btn btn-mint-solid" href="<?= url('/cyber-security#assessment') ?>">Start Assessment <?= icon('ArrowUpRight') ?></a></div>
  </div>
</section>

<?php $pastel = ['c-orange', 'c-blue', 'c-green', 'c-purple', 'c-pink', 'c-teal']; ?>
<section id="services" class="section section-sec">
  <div class="container">
    <div class="reveal sec-head"><div class="eyebrow">// 06 — Services</div><h2 class="h2"><?= e(t('services.title')) ?></h2><p class="lead"><?= e(t('services.subtitle')) ?></p></div>
    <div class="grid sm-2 lg-3 mt-8"><?php foreach ($services as $i => $s): ?><div class="svc reveal card-hover"><div class="icon-circle <?= $pastel[$i % 6] ?>"><?= icon($s['icon']) ?></div><h3><?= e($s['title']) ?></h3><p><?= e($s['description']) ?></p></div><?php endforeach; ?></div>
    <div class="cta-band reveal"><div><h3>Explore the full service practice</h3><p>Managed IT, cloud, hardware, custom development and license management — across all four regions.</p></div><a class="btn btn-primary" href="<?= url('/services') ?>">View All Services <?= icon('ArrowUpRight') ?></a></div>
  </div>
</section>

<section id="testimonials" class="section">
  <div class="container">
    <div class="reveal sec-head center"><div class="eyebrow">// 08 — Testimonials</div><h2 class="h2"><?= e(t('testimonials.title')) ?></h2><p class="lead"><?= e(t('testimonials.subtitle')) ?></p></div>
    <?php if (!$testimonials): ?><div class="empty mt-8">No testimonials yet. Add them from the admin panel.</div><?php else: ?>
    <div class="grid sm-2 lg-3 mt-8"><?php foreach ($testimonials as $tm): ?><div class="testi reveal"><span class="q"><?= icon('Quote') ?></span><p>“<?= e($tm['content']) ?>”</p><div class="testi-foot"><div class="avatar"><?php if ($tm['avatar_url']): ?><img src="<?= e(img($tm['avatar_url'])) ?>" alt="<?= e($tm['name']) ?>"><?php else: ?><?= e(mb_substr($tm['name'], 0, 1)) ?><?php endif; ?></div><div><div class="small" style="font-weight:600"><?= e($tm['name']) ?></div><?php if ($tm['role']): ?><div class="xs muted"><?= e($tm['role']) ?></div><?php endif; ?></div><div class="stars"><?php for ($i = 0; $i < 5; $i++) echo icon('Star', 'ico xs' . ($i < (int)$tm['rating'] ? '' : ' muted')); ?></div></div></div><?php endforeach; ?></div><?php endif; ?>
  </div>
</section>

<section id="global" class="section section-sec">
  <div class="container">
    <div class="reveal sec-head"><div class="eyebrow">// 07 — Global Connect</div><h2 class="h2"><?= e(t('global.title')) ?></h2></div>
    <div class="grid lg-2 mt-8" style="align-items:start">
      <div class="region-tabs reveal"><?php foreach (Region::all() as $c => $o): ?><a class="region-tab<?= $c === $regionCode ? ' active' : '' ?>" href="<?= url("/prefs?region=$c&back=" . urlencode('/#global')) ?>"><span class="dot"></span><b><?= e($c) ?></b><span><?= e($o['city']) ?></span><small data-tz="<?= e($o['tz']) ?>"><?= e(local_time($o['tz'])) ?></small></a><?php endforeach; ?></div>
      <?= App\Core\View::render('sections.office_card', ['r' => $r]) ?>
    </div>
  </div>
</section>
