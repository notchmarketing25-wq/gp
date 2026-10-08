<?php
class RetailerPublicController extends Controller
{
    public function index(): void
    {
        $retailers = Database::fetchAll("SELECT * FROM retailers WHERE is_active=1 ORDER BY sort_order, name");
        $byGov = [];
        $stores = []; $onlinePartners = []; $mapPoints = [];
        foreach ($retailers as $r) {
            if (($r['type'] ?? 'store') === 'online') {
                $onlinePartners[] = $r;
            } else {
                $stores[] = $r;
                $byGov[$r['governorate'] ?: 'أخرى'][] = $r;
                if ($r['latitude'] && $r['longitude']) {
                    $mapPoints[] = ['id'=>$r['id'],'name'=>$r['name'],'lat'=>(float)$r['latitude'],'lng'=>(float)$r['longitude'],'address'=>$r['address'],'logo'=>$r['logo']?uploadUrl($r['logo']):null,'gov'=>$r['governorate']?:'أخرى'];
                }
            }
        }
        ksort($byGov, SORT_STRING|SORT_FLAG_CASE);
        $governorates = array_keys($byGov);
        $this->view('store.pages.retailers', compact('retailers', 'byGov', 'stores', 'onlinePartners', 'mapPoints', 'governorates'));
    }
}
