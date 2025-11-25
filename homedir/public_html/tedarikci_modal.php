<?php 
       $sth = $conn->prepare('SELECT id, firma_adi as tedarikci_kodu, tedarikci_unvani FROM tedarikciler WHERE firma_id = :firma_id');
       $sth->bindParam('firma_id', $_SESSION['firma_id']);
       $sth->execute();
       $birimler = $sth->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="modal fade" id="tedarikciModal" tabindex="-1" aria-labelledby="tedarikciModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="padding-top:10px;padding-bottom:10px">
                    <h5 class="modal-title" id="tedarikciModalLabel">Tedarikci Listesi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body ptop-8 pbtm-8">
                    <!-- Arama Kutusu -->
                    <div class="search-container mb-1">
                        <input type="text" class="form-control" id="searchInputTedarikci" placeholder="Ara...">
                        <span class="search-icon">🔍</span>
                    </div>
                    <!-- Ölçü Birimleri Tablosu -->
                    <div style="height: 300px; overflow-y: auto;">
                    <table class="table table-striped table-bordered table-sm table-hover" id="tedarikciTable">
                        <thead>
                            <tr>
                                <th class="text-center ptop-5 pbtm-5">Id</th> 
                                <th class="ptop-5 pbtm-5">Tanım</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                foreach ($birimler as $birim) {
                                    echo '<tr class="tedarikci-row" data-id="' . $birim['id'] . '">';
                                    echo '<td class="text-center ptop-5 pbtm-5">' . $birim['id'] . '</td>';
                                    echo '<td class="ptop-5 pbtm-5">' . $birim['tedarikci_unvani'] . '</td>';
                                    echo '</tr>';
                                }
                            ?> 
                        </tbody>
                    </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>