<?php 
       $sth = $conn->prepare('SELECT * FROM birimler WHERE firma_id = :firma_id');
       $sth->bindParam('firma_id', $_SESSION['firma_id']);
       $sth->execute();
       $birimler = $sth->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="modal fade" id="unitModal" tabindex="-1" aria-labelledby="unitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="padding-top:10px;padding-bottom:10px">
                    <h5 class="modal-title" id="unitModalLabel">Ölçü Birimleri Listesi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body ptop-8 pbtm-8">
                    <!-- Arama Kutusu -->
                    <div class="search-container mb-1">
                        <input type="text" class="form-control" id="searchInputUnit" placeholder="Ara...">
                        <span class="search-icon">🔍</span>
                    </div>
                    <!-- Ölçü Birimleri Tablosu -->
                    <table class="table table-striped table-bordered table-sm table-hover" id="unitTable">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Ölçü Birimi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                foreach ($birimler as $birim) {
                                    echo '<tr class="unit-row" data-id="' . $birim['id'] . '">';
                                    echo '<td class="text-center ptop-5 pbtm-5">' . $birim['id'] . '</td>';
                                    echo '<td class="ptop-5 pbtm-5">' . $birim['ad'] . '</td>';
                                    echo '</tr>';
                                }
                            ?> 
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>