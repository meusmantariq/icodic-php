<?php use App\Models\PageBlock; $site = App\Models\Repo::settings();
$hero = PageBlock::get('projects', 'hero', ['title' => 'Proven global implementations', 'subtitle' => 'A selection of ERP implementations, software deployments and web projects delivered across the UK, the UAE, India and Pakistan.']); ?>
<?= App\Core\View::render('partials.page_hero', ['eyebrow' => 'Project Gallery', 'heading' => $hero['title'], 'sub' => $hero['subtitle']]) ?>
<section class="section"><div class="container">
  <?php if (!$projects): ?><div class="empty">Project case studies will appear here.</div><?php else: ?>
  <div class="grid sm-2 lg-3"><?php foreach ($projects as $p) echo App\Core\View::render('sections.project_card', ['p' => $p, 'site' => $site]); ?></div><?php endif; ?>
</div></section>
