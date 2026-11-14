<?php declare(strict_types=1); namespace App\Controllers\Admin; use App\Core\{Auth,Request};
final class ItemMasterController {
 private function ctl(Request $r): EntityController { Auth::require($r); return new EntityController(); }
 public function index(Request $r):mixed{return $this->ctl($r)->index($r,'software');}
 public function create(Request $r):mixed{return $this->ctl($r)->create($r,'software');}
 public function store(Request $r):never{$this->ctl($r)->store($r,'software'); exit;}
 public function edit(Request $r,string $id):mixed{return $this->ctl($r)->edit($r,'software',$id);}
 public function update(Request $r,string $id):never{$this->ctl($r)->update($r,'software',$id); exit;}
 public function destroy(Request $r,string $id):never{$this->ctl($r)->destroy($r,'software',$id); exit;}
}
