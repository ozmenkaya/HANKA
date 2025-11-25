<?php
    require_once "include/oturum_kontrol.php"; 
?>
<div class="modal fade" id="stokTuruModal" tabindex="-1" aria-labelledby="stokTuruModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="padding-top:10px;padding-bottom:10px">
                    <h5 class="modal-title" id="stokTuruModal">Stok Türü Listesi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body ptop-8 pbtm-8">
                    <!-- Arama Kutusu -->
                    <div class="search-container mb-1">
                        <input type="text" class="form-control" id="searchInputStokTuru" placeholder="Ara...">
                        <span class="search-icon">🔍</span>
                    </div>
                    <!-- Ölçü Birimleri Tablosu -->
                    <table class="table table-striped table-bordered table-sm table-hover" id="stokTuruTable">
                        <thead>
                            <tr>
                                <th class="text-center">Kod</th>
                                <th>Açıklama</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="stok-turu-row" data-id="M">
                                <td class="text-center ptop-5 pbtm-5">M</td>
                                <td class="ptop-5 pbtm-5">Mamül</td>
                            </tr>
                            <tr class="stok-turu-row" data-id="Y">
                                <td class=text-center ptop-5 pbtm-5">Y</td>
                                <td class="ptop-5 pbtm-5">Yarı Mamül</td> 
                            </tr>
                            <tr class="stok-turu-row" data-id="A">
                                <td class="text-center ptop-5 pbtm-5">A</td>
                                <td class="ptop-5 pbtm-5">Yan Ürün</td>
                            </tr>
                            <tr class="stok-turu-row" data-id="H"> 
                                <td class="text-center ptop-5 pbtm-5">H</td>
                                <td class="ptop-5 pbtm-5">Hammadde</td>
                            </tr>
                            <tr class="stok-turu-row" data-id="B"> 
                                <td class="text-center ptop-5 pbtm-5">B</td>
                                <td class="ptop-5 pbtm-5">Ambalaj Malzemesi</td>
                            </tr>
                            <tr class="stok-turu-row" data-id="T"> 
                                <td class="text-center ptop-5 pbtm-5">T</td>
                                <td class="ptop-5 pbtm-5">Ticari Mal</td>
                            </tr>
                            <tr class="stok-turu-row" data-id="D"> 
                                <td class="text-center ptop-5 pbtm-5">D</td>
                                <td class="ptop-5 pbtm-5">Diğer</td>
                            </tr>
                            <tr class="stok-turu-row" data-id="F"> 
                                <td class="text-center ptop-5 pbtm-5">F</td>
                                <td class="ptop-5 pbtm-5">Fason</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>