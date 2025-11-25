 <?php 
        require_once 'birim_modal.php'; 
        require_once 'stok_turu_modal.php'; 
        require_once 'depo_modal.php'; 
        require_once 'para_birimi_modal.php'; 
        require_once 'tedarikci_modal.php'; 
        require_once 'grup_modal.php';
        require_once 'kod1_modal.php';
        require_once 'kod2_modal.php';
        require_once 'kod3_modal.php';
        require_once 'kod4_modal.php';
        require_once 'kod5_modal.php';
 ?>

       
<div class="row mt-2">
    <div class="col-xl-12" style="padding: 0;">
        <div class="card">
            <div class="card-body"> 
            <div class="mb-3">
                <form>
                    <div id="basicwizard"> 
                        <ul class="nav nav-pills bg-light nav-justified form-wizard-header mb-3" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a href="#basictab1" data-bs-toggle="tab" class="nav-link rounded-0 pt-2 pb-2 active" aria-selected="true" role="tab"> 
                                    <i class="mdi mdi-credit-card me-1"></i>
                                    <span class="d-none d-sm-inline">Stok Kartı 1</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#basictab2" data-bs-toggle="tab" class="nav-link rounded-0 pt-2 pb-2" aria-selected="false" role="tab" tabindex="-1">
                                    <i class="mdi mdi-credit-card-multiple me-1"></i>
                                    <span class="d-none d-sm-inline">Stok Kartı 2</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#basictab3" data-bs-toggle="tab" class="nav-link rounded-0 pt-2 pb-2" aria-selected="false" role="tab" tabindex="-1">
                                    <i class="mdi mdi mdi-currency-usd me-1"></i>
                                    <span class="d-none d-sm-inline">Fiyatlar</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#basictab4" data-bs-toggle="tab" class="nav-link rounded-0 pt-2 pb-2" aria-selected="false" role="tab" tabindex="-1">
                                    <i class="mdi mdi mdi-package-variant-closed me-1"></i>
                                    <span class="d-none d-sm-inline">Kart Nitelikleri</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#basictab5" data-bs-toggle="tab" class="nav-link rounded-0 pt-2 pb-2" aria-selected="false" role="tab" tabindex="-1">
                                    <i class="mdi mdi mdi-package-variant-closed me-1"></i>
                                    <span class="d-none d-sm-inline">Depo Bakiyesi</span>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content b-0 mb-0 pt-0">
                            <div class="tab-pane active show" id="basictab1" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="row mb-2">
                                            <label class="col-md-3 col-form-label" for="stokKodu">Stok Kodu</label>
                                            <div class="col-md-9">
                                                <div class="input-group" style="width:60%">
                                                    <input type="text" class="form-control" id="stokKodu" name="stokKodu">
                                                    <button type="button" class="btn btn-success waves-effect waves-light">
                                                        <i class="mdi mdi-vector-selection"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-md-3 col-form-label" for="stokAdi">Stok Adı</label>
                                            <div class="col-md-9">
                                                <textarea class="form-control" id="stokAdi" name="stokAdi" rows="3"></textarea>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-md-3 col-form-label" for="stokAdi2">Stok Adı 2</label>
                                            <div class="col-md-9">
                                                <textarea class="form-control" id="stokAdi2" name="stokAdi2" rows="3"></textarea>
                                            </div>
                                        </div> 
                                        <div class="row mb-2">
                                            <label class="col-md-3 col-form-label" for="girisDepo">Giriş Depo</label>
                                            <div class="col-md-3">
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="girisDepo" name="girisDepo">
                                                    <button type="button" class="btn btn-success waves-effect waves-light" id="openGirisDepoModal">
                                                        <i class="mdi mdi-vector-selection"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <label class="col-md-3 col-form-label" for="cikisDepo">Çıkış Depo</label>
                                            <div class="col-md-3">
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="cikisDepo" name="cikisDepo">
                                                    <button type="button" class="btn btn-success waves-effect waves-light" id="openCikisDepoModal">
                                                        <i class="mdi mdi-vector-selection"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-md-3 col-form-label" for="riskSuresi">Risk Süresi</label>
                                            <div class="col-md-3">
                                                <input type="number" class="form-control" id="riskSuresi" name="riskSuresi">
                                            </div>
                                            <label class="col-md-3 col-form-label" for="zamanBirimi">Zaman Birimi</label>
                                            <div class="col-md-3">
                                                <input type="text" class="form-control" id="zamanBirimi" name="zamanBirimi">
                                            </div>
                                        </div>
                                    </div> 
                                    <div class="col-md-4" style="margin-bottom:10px">
                                        <div style="width:200px">
                                            <div class="mb-1" style="border:1px solid #ddd; width:200px;height:200px"></div>
                                            <div class="d-flex justify-content-end">
                                                <label for="formFile" class="custom-file-upload">
                                                Dosya Seç
                                                </label>
                                                <input type="file" id="formFile">
                                            </div>
                                        </div>
                                    </div>
                                </div> 
                            </div>

                            <div class="tab-pane" id="basictab2" role="tabpanel">
                            <div class="row">
                                <div class="col-6">
                                    <div class="row mb-2">
                                        <label class="col-md-3 col-form-label" for="olcuBirimi">Ölçü Birimi</label>
                                        <div class="col-md-3">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="olcuBirimi" name="olcuBirimi">
                                                <button type="button" class="btn btn-success waves-effect waves-light" id="openUnitModal">
                                                    <i class="mdi mdi-vector-selection"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <label class="col-md-3 col-form-label" for="stokTuru">Türü</label>
                                        <div class="col-md-3">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="stokTuru" name="stokTuru">
                                                <button type="button" class="btn btn-success waves-effect waves-light" id="openStokTuruModal">
                                                    <i class="mdi mdi-vector-selection"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <label class="col-md-3 col-form-label" for="oncekiKodu">Önceki Kodu</label>
                                        <div class="col-md-3">
                                            <input type="text" class="form-control" id="oncekiKodu" name="oncekiKodu">
                                        </div>
                                        <label class="col-md-3 col-form-label" for="sonrakiKodu">Sonraki Kodu</label>
                                        <div class="col-md-3">
                                            <input type="text" class="form-control" id="sonrakiKodu" name="sonrakiKodu">
                                        </div>
                                    </div>
                                    <div class="row mb-2"> 
                                        <label class="col-md-3 col-form-label" for="nakliyeTutari">Nakliye Tutarı</label>
                                        <div class="col-md-3">
                                            <input type="number" class="form-control" id="nakliyeTutari" name="nakliyeTutari" step="0.01">
                                        </div>
                                        <label class="col-md-3 col-form-label" for="blokaj">Blokaj</label>
                                        <div class="col-md-3 d-flex align-items-center">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="blokaj">
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                                <div class="col-6">
                                </div>
                            </div> 
                            <div class="row">
                                <div class="col-6">
                                    <div class="card">
                                        <div class="card-header bg-blue py-3 text-white">
                                            <h5 class="card-title mb-0 text-white">Ebat Bilgileri</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="col-12">
                                                <div class="row mb-2">
                                                    <label class="col-md-3 col-form-label" for="en">En</label>
                                                    <div class="col-md-9">
                                                        <input type="number" class="form-control" id="en" name="en" step="0.1">
                                                    </div>
                                                </div>
                                                <div class="row mb-2">
                                                    <label class="col-md-3 col-form-label" for="boy">Boy</label>
                                                    <div class="col-md-9">
                                                        <input type="number" class="form-control" id="boy" name="boy" step="0.1">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <label class="col-md-3 col-form-label" for="yukseklik">Yükseklik</label>
                                                    <div class="col-md-9">
                                                        <input type="number" class="form-control" id="yukseklik" name="yukseklik" step="0.1">
                                                    </div>
                                                </div>
                                            </div> 
                                        </div> 
                                    </div>
                                </div> 
                                <div class="col-6"> 
                                    <div class="card">
                                        <div class="card-header bg-blue py-3 text-white">
                                            <h5 class="card-title mb-0 text-white">Barkod Bilgileri</h5>
                                        </div>
                                        <div class="card-body">
                                                <div class="row mb-2">
                                                    <label class="col-md-3 col-form-label" for="barkod1">Barkod 1</label>
                                                    <div class="col-md-9">
                                                        <input type="text" class="form-control" id="barkod1" name="barkod1" maxlength="50">
                                                    </div>
                                                </div>
                                                <div class="row mb-2">
                                                    <label class="col-md-3 col-form-label" for="barkod2">Barkod 2</label>
                                                    <div class="col-md-9">
                                                        <input type="text" class="form-control" id="barkod2" name="barkod2" maxlength="50">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <label class="col-md-3 col-form-label" for="barkod3">Barkod 3</label>
                                                    <div class="col-md-9">
                                                        <input type="text" class="form-control" id="barkod3" name="barkod3" maxlength="50">
                                                    </div>
                                                </div>
                                        </div>
                                    </div>
                                </div>
                            </div> 
                            <div class="row">
                                <div class="col-12">
                                        <div class="card">
                                        <div class="card-header bg-blue py-3 text-white">
                                            <h5 class="card-title mb-0 text-white">Rapor Kodları</h5>
                                        </div>
                                        <div class="card-body">
                                                <div class="row mb-2">
                                                    <label class="col-md-2 col-form-label" for="cariKodu">Cari/S Kodu</label>
                                                    <div class="col-md-3">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" id="cariKodu" name="cariKodu" maxlength="50">
                                                            <button type="button" class="btn btn-success waves-effect waves-light" id="opensCariModal">
                                                                <i class="mdi mdi-vector-selection"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-7">
                                                    </div>
                                                </div>
                                                <div class="row mb-2">
                                                    <label class="col-md-2 col-form-label" for="ureticiKodu">Üretici Kodu</label>
                                                    <div class="col-md-3"> 
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" id="ureticiKodu" name="ureticiKodu" maxlength="50">
                                                            <button type="button" class="btn btn-success waves-effect waves-light" id="openUreticiModal">
                                                                <i class="mdi mdi-vector-selection"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-7">
                                                    </div>
                                                </div>
                                                <div class="row mb-2">
                                                    <label class="col-md-2 col-form-label" for="grupKodu">Grup Kodu</label>
                                                    <div class="col-md-3">
                                                        <div class="input-group">
                                                           <input type="text" class="form-control" id="grupKodu" name="grupKodu" maxlength="50">
                                                            <button type="button" class="btn btn-success waves-effect waves-light" id="openGrupKoduModal">
                                                                <i class="mdi mdi-vector-selection"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-7">
                                                    </div>
                                                </div>
                                                <div class="row mb-2">
                                                    <label class="col-md-2 col-form-label" for="kod1">Kod1</label>
                                                    <div class="col-md-3">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" id="kod1" name="kod1" maxlength="50">
                                                            <button type="button" class="btn btn-success waves-effect waves-light" id="openKod1Modal">
                                                                <i class="mdi mdi-vector-selection"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-7">
                                                    </div>
                                                </div>
                                                <div class="row mb-2">
                                                    <label class="col-md-2 col-form-label" for="kod2">Kod2</label>
                                                    <div class="col-md-3">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" id="kod2" name="kod2" maxlength="50">
                                                            <button type="button" class="btn btn-success waves-effect waves-light" id="openKod2Modal">
                                                                <i class="mdi mdi-vector-selection"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-7">
                                                    </div>
                                                </div>
                                                <div class="row mb-2">
                                                    <label class="col-md-2 col-form-label" for="kod3">Kod3</label>
                                                    <div class="col-md-3">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" id="kod3" name="kod3" maxlength="50">
                                                            <button type="button" class="btn btn-success waves-effect waves-light" id="openKod3Modal">
                                                                <i class="mdi mdi-vector-selection"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-7">
                                                    </div>
                                                </div>
                                                <div class="row mb-2">
                                                    <label class="col-md-2 col-form-label" for="kod4">Kod4</label>
                                                    <div class="col-md-3">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" id="kod4" name="kod4" maxlength="50">
                                                            <button type="button" class="btn btn-success waves-effect waves-light" id="openKod4Modal">
                                                                <i class="mdi mdi-vector-selection"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-7">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <label class="col-md-2 col-form-label" for="kod5">Kod5</label>
                                                    <div class="col-md-3">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" id="kod5" name="kod5" maxlength="50">
                                                            <button type="button" class="btn btn-success waves-effect waves-light" id="openKod5Modal">
                                                                <i class="mdi mdi-vector-selection"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-7">
                                                    </div>
                                                </div>
                                        </div>
                                    </div>
                                </div> 
                            </div>
                            </div>

                            <div class="tab-pane" id="basictab3" role="tabpanel">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="card">
                                            <div class="card-header bg-blue py-3 text-white">
                                                <h5 class="card-title mb-0 text-white">Satış</h5>
                                            </div>
                                            <div class="card-body"> 
                                                <div class="row mb-2">
                                                    <label class="col-md-2 col-form-label" for="sfiyat1">Fiyat 1</label>
                                                    <div class="col-md-3">
                                                        <input type="number" class="form-control" id="sfiyat1" name="sfiyat1" step="0.01">
                                                    </div>
                                                    <div class="col-md-3">    
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" id="spb1" name="spb1">
                                                            <button type="button" class="btn btn-success waves-effect waves-light" id="opensPb1Modal">
                                                                <i class="mdi mdi-vector-selection"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4"></div>
                                                </div>  
                                                <div class="row mb-2">
                                                    <label class="col-md-2 col-form-label" for="sfiyat2">Fiyat 2</label>
                                                    <div class="col-md-3">
                                                        <input type="number" class="form-control" id="sfiyat2" name="sfiyat2" step="0.01">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" id="spb2" name="spb2">
                                                            <button type="button" class="btn btn-success waves-effect waves-light" id="opensPb2Modal">
                                                                <i class="mdi mdi-vector-selection"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4"></div>
                                                </div>  
                                                <div class="row mb-2">
                                                    <label class="col-md-2 col-form-label" for="sfiyat3">Fiyat 3</label>
                                                    <div class="col-md-3">
                                                        <input type="number" class="form-control" id="sfiyat3" name="sfiyat3" step="0.01"> 
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" id="spb3" name="spb3">
                                                            <button type="button" class="btn btn-success waves-effect waves-light" id="opensPb3Modal">
                                                                <i class="mdi mdi-vector-selection"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4"></div>
                                                </div>  
                                            </div> 
                                        </div>
                                    </div>  
                                    <div class="col-6">
                                        <div class="card">
                                            <div class="card-header bg-blue py-3 text-white">
                                                <h5 class="card-title mb-0 text-white">Alış</h5>
                                            </div>
                                            <div class="card-body"> 
                                                <div class="row mb-2">
                                                    <label class="col-md-2 col-form-label" for="afiyat1">Fiyat 1</label>
                                                    <div class="col-md-3">
                                                        <input type="number" class="form-control" id="afiyat1" name="afiyat1" step="0.01">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" id="apb1" name="apb1">
                                                            <button type="button" class="btn btn-success waves-effect waves-light" id="openaPb1Modal">
                                                                <i class="mdi mdi-vector-selection"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6"></div>
                                                </div>  
                                                <div class="row mb-2">
                                                    <label class="col-md-2 col-form-label" for="afiyat2">Fiyat 2</label>
                                                    <div class="col-md-3">
                                                        <input type="number" class="form-control" id="afiyat2" name="afiyat2" step="0.01">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" id="apb2" name="apb2">
                                                            <button type="button" class="btn btn-success waves-effect waves-light" id="openaPb2Modal">
                                                                <i class="mdi mdi-vector-selection"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6"></div>
                                                </div>  
                                                <div class="row mb-2">
                                                    <label class="col-md-2 col-form-label" for="afiyat3">Fiyat 3</label>
                                                    <div class="col-md-3">
                                                        <input type="number" class="form-control" id="afiyat3" name="afiyat3" step="0.01">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" id="apb3" name="apb3">
                                                            <button type="button" class="btn btn-success waves-effect waves-light" id="openaPb3Modal">
                                                                <i class="mdi mdi-vector-selection"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6"></div>
                                                </div>  
                                            </div> 
                                        </div>
                                    </div>
                                </div>  
                            </div>

                            <div class="tab-pane" id="basictab4" role="tabpanel">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="text-center">
                                            <h2 class="mt-0"><i class="mdi mdi-check-all"></i></h2>
                                            <h3 class="mt-0">Thank you !</h3>

                                            <p class="w-75 mb-2 mx-auto">Quisque nec turpis at urna dictum luctus. Suspendisse convallis dignissim eros at volutpat. In egestas mattis dui. Aliquam
                                                mattis dictum aliquet.</p>

                                            <div class="mb-3">
                                                <div class="form-check d-inline-block">
                                                    <input type="checkbox" class="form-check-input" id="customCheck1">
                                                    <label class="form-check-label" for="customCheck1">I agree with the Terms and Conditions</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>  
                                </div>  
                            </div>

                            <div class="tab-pane" id="basictab5" role="tabpanel">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="text-center">
                                            <h2 class="mt-0"><i class="mdi mdi-check-all"></i></h2>
                                            <h3 class="mt-0">Thank you !</h3>

                                            <p class="w-75 mb-2 mx-auto">Quisque nec turpis at urna dictum luctus. Suspendisse convallis dignissim eros at volutpat. In egestas mattis dui. Aliquam
                                                mattis dictum aliquet.</p>

                                            <div class="mb-3">
                                                <div class="form-check d-inline-block">
                                                    <input type="checkbox" class="form-check-input" id="customCheck1">
                                                    <label class="form-check-label" for="customCheck1">I agree with the Terms and Conditions</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>  
                                </div>  
                            </div>

                            <ul class="list-inline wizard mb-0"> 
                                <li class="next list-inline-item float-end disabled">
                                    <a href="javascript: void(0);" class="btn btn-blue">Kaydet</a>
                                </li>
                            </ul>

                        </div> <!-- tab-content -->
                    </div> <!-- end #basicwizard-->
                </form>

            </div> <!-- end card-body -->
        </div> <!-- end card-->
    </div> 
</div>