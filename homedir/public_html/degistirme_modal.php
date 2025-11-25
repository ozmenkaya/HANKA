<!-- Degistirme Modal -->
<div class="modal fade" id="degistirme-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="degistirmeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light pt-1 pb-1">
                <h4 class="col-md-11 modal-title text-center" id="degistirmeModalLabel">Değiştirme Logları</h4>
                <button type="button" class="col-md-1 btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-1 p-3"> 
                    <div class="row">
                        <table class="table table-sm table-bordered" id="degistirmeTablosu">
                            <thead>
                                <tr> 
                                    <th class="pt-1 pb-1 table_sm_pd">Değiştirme Sebebi</th>
                                    <th class="pt-1 pb-1 table_sm_pd text-center">Sorun Bildirimi</th>
                                    <th class="pt-1 pb-1 table_sm_pd">Personel</th>
                                    <th class="pt-1 pb-1 table_sm_pd text-center">Tarih</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-blue waves-effect waves-light" data-bs-dismiss="modal">Kapat</button>
                    </div> 
            </div>
        </div>
    </div>
</div>