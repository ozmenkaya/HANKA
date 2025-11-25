<?php 
     $logo = isset($_SESSION['logo']) ? htmlspecialchars($_SESSION['logo'], ENT_QUOTES, 'UTF-8') : 'default-logo.png';
     
     // TTS ayarını veritabanından al
     $tts_enabled = 0; // Varsayılan: kapalı
     if (isset($_SESSION['firma_id'])) {
         try {
             $stmt = $conn->prepare("SELECT tts_enabled, tts_auto_play FROM ai_agent_settings WHERE firma_id = :firma_id LIMIT 1");
             $stmt->execute(['firma_id' => $_SESSION['firma_id']]);
             $ai_settings = $stmt->fetch(PDO::FETCH_ASSOC);
             if ($ai_settings) {
                 $tts_enabled = $ai_settings['tts_enabled'];
                 $tts_auto_play = $ai_settings['tts_auto_play'];
             }
         } catch (Exception $e) {
             error_log("TTS ayarları okunamadı: " . $e->getMessage());
         }
     }
?>
<div class="navbar-custom">
    <div class="topbar">
        <div class="topbar-menu d-flex align-items-center gap-1" style="width: 100% !important; max-width: 100% !important;">

            <!-- Topbar Brand Logo -->
            <div class="logo-box">
                <!-- Brand Logo Light -->
                <a href="index.html" class="logo-light">
                    <img src="assets/images/logo-light.png" alt="logo" class="logo-lg">
                    <img src="dosyalar/logo/<?php echo $logo ?>" alt="small logo" class="logo-sm">
                </a>

                <!-- Brand Logo Dark -->
                <a href="index.html" class="logo-dark">
                    <img src="assets/images/logo-dark.png" alt="dark logo" class="logo-lg">
                    <img src="dosyalar/logo/<?php echo $logo ?>" alt="small logo" class="logo-sm">
                </a>
            </div>

            <!-- Sidebar Menu Toggle Button -->
            <button class="button-toggle-menu">
                <i class="mdi mdi-menu"></i>
            </button>

            <!-- AI SEARCH BAR - YENİ EKLEME -->
            <div class="flex-grow-1 px-3 d-none d-lg-block" style="max-width: 100% !important; width: 100% !important; flex: 1 1 100% !important;">
                <div class="ai-search-container" style="max-width: 1800px !important; width: 100% !important; margin: 0 auto;">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-robot text-primary"></i>
                        </span>
                        <input type="text" 
                               id="aiSearchInput" 
                               class="form-control border-start-0 border-end-0" 
                               placeholder="AI Asistan'a sorun... (örn: Helmex firması sipariş ortalaması nedir?)"
                               autocomplete="off">
                        <button class="btn btn-secondary" id="aiVoiceBtn" type="button" title="Sesli Sor">
                            <i class="fas fa-microphone"></i>
                        </button>
                        <button class="btn btn-primary" id="aiSearchBtn" type="button">
                            <i class="fas fa-search me-1"></i> Sor
                        </button>
                    </div>
                    <!-- Autocomplete Dropdown -->
                    <div id="aiSearchSuggestions" class="ai-suggestions-dropdown" style="display: none;"></div>
                </div>
            </div>

        </div>

        <ul class="topbar-menu d-flex align-items-center">
             
            <!-- Notofication dropdown -->
            <li class="dropdown notification-list">
                <a class="nav-link arrow-none" href="/index.php?url=geri_bildirim">
                    <i class="fe-bell font-22"></i>
                    <span class="badge bg-danger rounded-circle noti-icon-badge"><?php echo gorunmeyen_geri_bildirim_sayisi(); ?></span>
                </a> 
            </li>

            <!-- Light/Dark Mode Toggle Button -->
            <li class="d-none d-sm-inline-block">
                <div class="nav-link waves-effect waves-light" id="light-dark-mode">
                    <i class="ri-moon-line font-22"></i>
                </div>
            </li>

            <!-- User Dropdown -->
            <li class="dropdown">
                <a class="nav-link dropdown-toggle nav-user me-0 waves-effect waves-light" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                    <span class="ms-1 d-none d-md-inline-block">
                        <?php echo $_SESSION['ad'].' '.$_SESSION['soyad']; ?>
                        <i class="mdi mdi-chevron-down"></i>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end profile-dropdown ">
                    <!-- item-->
                    <div class="dropdown-header noti-title">
                        <h6 class="text-overflow m-0">Hoşgeldiniz !</h6>
                    </div>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item">
                        <i class="fe-user"></i>
                        <span>Profilim</span>
                    </a>
   
                    <div class="dropdown-divider"></div>

                    <!-- item-->
                    <a href="login_kontrol.php?islem=cikis-yap" class="dropdown-item notify-item">  
                        <i class="fe-log-out"></i>
                        <span>Çıkış</span>
                    </a>

                </div>
            </li>

            <!-- User Dropdown -->
            <li class="dropdown">
                <a class="nav-link dropdown-toggle nav-user me-0 waves-effect waves-light" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                    <i class="fe-settings font-22"></i>
                     <i class="mdi mdi-chevron-down"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end profile-dropdown ">
                 
                    <!-- item-->
                    <!-- <a href="javascript:void(0);" class="dropdown-item notify-item">
                        <i class="fe-user"></i>
                        <span>Profilim</span>
                    </a> -->

                    <?php if(in_array($_SESSION['yetki_id'], [SUPER_ADMIN_YETKI_ID,ADMIN_YETKI_ID] )){ ?>
                        <a href="/index.php?url=firma_ayarlar" class="dropdown-item notify-item" style="padding: 0px 24px;">
                            <span class="menu-icon"><i class="mdi mdi-image-filter-vintage"></i></span>
                            <span class="menu-text"> Firma Ayarlar </span>
                        </a>

                        <a href="/index.php?url=yedekleme" class="dropdown-item notify-item" style="padding: 0px 24px;">
                            <span class="menu-icon"><i class="mdi mdi-database"></i></span>
                            <span class="menu-text"> Yedekleme </span>
                        </a>       

                        <div class="dropdown-divider"></div>

                    <?php } ?>
  
                    <a href="/index.php?url=sifre_guncelle" class="dropdown-item notify-item" style="padding: 0px 24px;">
                        <span class="menu-icon"><i class="mdi mdi-lock"></i></span>
                        <span class="menu-text"> Şifre Değiştir </span>
                    </a>
    
                    <?php if(in_array($_SESSION['yetki_id'], [SUPER_ADMIN_YETKI_ID,ADMIN_YETKI_ID] )){ ?> 
                        <div class="dropdown-divider"></div>

                        <a href="/index.php?url=birimler" class="dropdown-item notify-item" style="padding: 0px 24px;">
                            <span class="menu-icon"><i class="mdi mdi-altimeter"></i></span>
                            <span class="menu-text"> Birimler </span>
                        </a>

                        <div class="dropdown-divider"></div>

                        <a href="/index.php?url=ai_learning_dashboard" class="dropdown-item notify-item" style="padding: 0px 24px;">
                            <span class="menu-icon"><i class="mdi mdi-robot"></i></span>
                            <span class="menu-text"> AI İstatistikleri </span>
                        </a>

                        <a href="/index.php?url=ai_ayarlar" class="dropdown-item notify-item" style="padding: 0px 24px;">
                            <span class="menu-icon"><i class="mdi mdi-brain"></i></span>
                            <span class="menu-text"> AI Ayarları </span>
                        </a>
                    <?php } ?>
 
                    <?php if(in_array(STOK_GOR, $_SESSION['sayfa_idler'])){ ?> 
                            <div class="dropdown-divider"></div>

                            <a href="/index.php?url=stok_kalem" class="dropdown-item notify-item" style="padding: 0px 24px;">
                                <span class="menu-icon"><i class="mdi mdi-package-variant-closed"></i></span>
                                <span class="menu-text">Stok Kalem</span>
                            </a>
                    <?php } ?> 
                          
                    <?php if(in_array($_SESSION['yetki_id'], [SUPER_ADMIN_YETKI_ID,ADMIN_YETKI_ID] )){ ?>  

                        <div class="dropdown-divider"></div>

                        <a href="/index.php?url=siparis_form" class="dropdown-item notify-item" style="padding: 0px 24px;">
                            <span class="menu-icon"><i class="mdi mdi-equal-box"></i></span>
                            <span class="menu-text">Siparişler Form</span>
                        </a> 
                    <?php }?>
 
                    <?php if(in_array($_SESSION['yetki_id'], [SUPER_ADMIN_YETKI_ID,ADMIN_YETKI_ID] )){ ?>

                        <div class="dropdown-divider"></div>

                        <a href="/index.php?url=siparis_form_tipleri" class="dropdown-item notify-item" style="padding: 0px 24px;">
                            <span class="menu-icon"><i class="mdi mdi-newspaper"></i></span>           
                            <span class="menu-text">Sipariş Form Tipleri</span>
                        </a>
                    <?php }?> 

                    <?php if(in_array(SEKTOR_GOR, $_SESSION['sayfa_idler'])){  ?>

                        <div class="dropdown-divider"></div>

                        <a href="/index.php?url=sektor" class="dropdown-item notify-item" style="padding: 0px 24px;">
                            <span class="menu-icon"><i class="mdi mdi-chart-donut-variant"></i></span>
                            <span class="menu-text"> Sektörler </span>
                        </a>
                    <?php }?>
 
                    <?php if(in_array($_SESSION['yetki_id'], [SUPER_ADMIN_YETKI_ID,ADMIN_YETKI_ID] )){ ?>

                        <div class="dropdown-divider"></div>

                        <a href="/index.php?url=turler" class="dropdown-item notify-item" style="padding: 0px 24px;">
                            <span class="menu-icon"><i class="mdi mdi-blender"></i></span>
                            <span class="menu-text"> Türler </span>
                        </a>
                    <?php }?>

                    <div class="dropdown-divider"></div>

                        <a href="/index.php?url=sayfa_yetkiler" class="dropdown-item notify-item" style="padding: 0px 24px;">
                            <span class="menu-icon"><i class="mdi mdi-power-plug"></i></span>
                            <span class="menu-text"> Sayfa Yetkileri </span>
                        </a> 


                    <div class="dropdown-divider"></div>
 
                        <a href="/index.php?url=geri_bildirim" class="dropdown-item notify-item" style="padding: 0px 24px;">
                            <span class="menu-icon"><i class="mdi mdi-nutrition"></i></span>
                            <span class="menu-text"> Geri Bildirim </span>
                            <span class="badge bg-blue ms-auto">
                                <?php echo gorunmeyen_geri_bildirim_sayisi(); ?>
                            </span>
                        </a> 
                        
                    <div class="dropdown-divider"></div>
 
                        <a href="login_kontrol.php?islem=cikis-yap" class="dropdown-item notify-item" style="padding: 0px 24px;">
                            <span class="menu-icon"><i class="mdi mdi-exit-to-app"></i></span>
                            <span class="menu-text"> Çıkış </span>
                        </a> 
 
                </div>
            </li>
        </ul>
    </div>
</div>

<!-- AI SEARCH RESULT MODAL -->
<!-- AI Search Modal -->
<style>
.ai-modal-xl {
    max-width: 95vw !important;
    width: 95vw !important;
    margin: 1rem auto !important;
}
.ai-modal-body {
    max-height: calc(100vh - 150px) !important;
    overflow-y: auto !important;
}

#aiVoiceBtn {
    border-left: 0 !important;
}

#aiVoiceBtn.btn-danger {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.6;
    }
}

@media (max-width: 768px) {
    .ai-modal-xl {
        max-width: 100vw !important;
        margin: 0 !important;
    }
}
</style>
<div class="modal fade" id="aiSearchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog ai-modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white">
                    <i class="fas fa-robot me-2"></i>
                    AI Asistan Yanıtı
                </h5>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-sm btn-light me-2" id="aiSpeakerToggle" onclick="toggleSpeaker()" title="Seslendirmeyi Kapat/Aç">
                        <i class="fas fa-volume-up" id="speakerIcon"></i>
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body ai-modal-body">
                <div id="aiSearchLoading" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Yükleniyor...</span>
                    </div>
                    <p class="mt-3 text-muted">AI yanıt oluşturuyor...</p>
                </div>
                
                <div id="aiSearchResult" style="display: none;">
                    <!-- Question -->
                    <div class="alert alert-light border-start border-primary border-4 mb-4">
                        <h6 class="mb-0">
                            <i class="fas fa-question-circle me-2 text-primary"></i>
                            <strong id="aiQuestionText"></strong>
                        </h6>
                    </div>
                    
                    <!-- Answer -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="fas fa-lightbulb me-2 text-warning"></i>
                                Yanıt
                            </h6>
                            <div id="aiAnswerText" class="lead"></div>
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-database me-1"></i>
                                    <span id="aiRecordCount"></span> kayıt bulundu
                                    <span class="mx-2">•</span>
                                    <i class="fas fa-clock me-1"></i>
                                    <span id="aiResponseTime"></span>s
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Data Table -->
                    <div id="aiDataContainer" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">
                                <i class="fas fa-table me-2"></i>
                                Detaylı Veriler
                            </h6>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-success" onclick="exportToExcel()">
                                    <i class="mdi mdi-file-excel me-1"></i> Excel
                                </button>
                                <button type="button" class="btn btn-outline-danger" onclick="exportToPDF()">
                                    <i class="mdi mdi-file-pdf me-1"></i> PDF
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="aiDataTable" class="table table-sm table-hover table-bordered">
                                <thead class="table-light"></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- SQL Query (Collapsible) -->
                    <div class="card border-0 bg-light mt-4">
                        <div class="card-body">
                            <a class="d-flex justify-content-between align-items-center text-decoration-none" 
                               data-bs-toggle="collapse" 
                               href="#aiSqlCollapse">
                                <h6 class="mb-0">
                                    <i class="fas fa-code me-2"></i>
                                    SQL Sorgusu
                                </h6>
                                <i class="fas fa-chevron-down"></i>
                            </a>
                            <div class="collapse mt-3" id="aiSqlCollapse">
                                <pre class="bg-dark text-light p-3 rounded"><code id="aiSqlText"></code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Feedback -->
                    <div class="mt-4 text-center">
                        <p class="mb-2">Bu yanıt size yardımcı oldu mu?</p>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-success" onclick="submitAIFeedback(5)">
                                <i class="fas fa-thumbs-up me-1"></i> Çok İyi
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="submitAIFeedback(4)">
                                <i class="fas fa-smile me-1"></i> İyi
                            </button>
                            <button type="button" class="btn btn-outline-warning" onclick="submitAIFeedback(3)">
                                <i class="fas fa-meh me-1"></i> Orta
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="submitAIFeedback(2)">
                                <i class="fas fa-frown me-1"></i> Kötü
                            </button>
                        </div>
                    </div>
                </div>
                
                <div id="aiSearchError" class="alert alert-danger" style="display: none;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="aiErrorText"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mikrofon İzin Modal -->
<div class="modal fade" id="microphonePermissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-microphone-slash me-2"></i>
                    Mikrofon İzni Gerekli
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-microphone" style="font-size: 48px; color: #f39c12;"></i>
                </div>
                <p class="mb-3">Sesli arama kullanabilmek için mikrofon erişimine izin vermeniz gerekiyor.</p>
                
                <div class="alert alert-info">
                    <strong>Nasıl İzin Verilir?</strong>
                    <ol class="mb-0 mt-2" style="padding-left: 20px;">
                        <li>Tarayıcınızın adres çubuğunun solundaki <strong>🔒 kilit</strong> veya <strong>ⓘ bilgi</strong> ikonuna tıklayın</li>
                        <li><strong>"Site Ayarları"</strong> veya <strong>"İzinler"</strong> bölümünü açın</li>
                        <li><strong>"Mikrofon"</strong> iznini <span class="badge bg-success">İZİN VER</span> olarak ayarlayın</li>
                        <li>Sayfayı yenileyin ve tekrar deneyin</li>
                    </ol>
                </div>
                
                <div class="text-muted small">
                    <i class="fas fa-shield-alt me-1"></i>
                    Mikrofonunuz sadece siz konuştuğunuzda aktif olur ve kaydedilmez.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-sync me-1"></i> Sayfayı Yenile
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* AI Search Styles */
.flex-grow-1.px-3 {
    max-width: 100% !important;
    width: 100% !important;
    margin: 0 !important;
    flex: 1 1 auto !important;
    padding: 0 20px !important;
}

.ai-search-container {
    position: relative;
    max-width: 2000px !important;
    width: 100% !important;
    margin: 0 auto;
}

.ai-search-container .input-group {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

.ai-search-container .input-group-text {
    border: 1px solid #e3e6f0;
}

.ai-search-container .form-control {
    border: 1px solid #e3e6f0;
    padding: 0.6rem 1rem;
}

.ai-search-container .form-control:focus {
    box-shadow: none;
    border-color: #667eea;
}

.ai-suggestions-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #e3e6f0;
    border-top: none;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    margin-top: -1px;
}

.ai-suggestion-item {
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f8f9fc;
    transition: background 0.2s;
}

.ai-suggestion-item:hover {
    background: #f8f9fc;
}

.ai-suggestion-item:last-child {
    border-bottom: none;
}

.ai-suggestion-icon {
    color: #667eea;
    margin-right: 10px;
}

@media (max-width: 991px) {
    .ai-search-container {
        display: none;
    }
}
</style>

<script>
// AI Search JavaScript
let currentChatId = null;

// Örnek sorular
const exampleQuestions = [
    "Helmex firması sipariş ortalaması nedir?",
    "Bu ay kaç sipariş teslim edildi?",
    "Gökhan usta bu ay kaç makina arızası yaptı?",
    "En çok sipariş veren müşteri kim?",
    "Son 30 gün üretim toplamı nedir?",
    "Makina bazında üretim miktarları",
    "Personel performans raporu",
    "Geciken siparişler listesi"
];

// Search input event listeners
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("aiSearchInput");
    const searchBtn = document.getElementById("aiSearchBtn");
    const suggestionsBox = document.getElementById("aiSearchSuggestions");
    
    let autocompleteTimer = null;
    let currentSuggestions = [];
    let selectedSuggestionIndex = -1;
    
    // Autocomplete - kullanıcı yazarken önerileri getir
    searchInput.addEventListener("input", function(e) {
        const query = e.target.value.trim();
        
        if (query.length < 2) {
            suggestionsBox.style.display = "none";
            return;
        }
        
        // Debounce: 300ms bekle
        clearTimeout(autocompleteTimer);
        autocompleteTimer = setTimeout(() => {
            fetchAutocompleteSuggestions(query);
        }, 300);
    });
    
    // Klavye navigasyonu
    searchInput.addEventListener("keydown", function(e) {
        const suggestions = suggestionsBox.querySelectorAll(".suggestion-item");
        
        if (e.key === "ArrowDown") {
            e.preventDefault();
            selectedSuggestionIndex = Math.min(selectedSuggestionIndex + 1, suggestions.length - 1);
            updateSuggestionSelection(suggestions);
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            selectedSuggestionIndex = Math.max(selectedSuggestionIndex - 1, -1);
            updateSuggestionSelection(suggestions);
        } else if (e.key === "Enter" && selectedSuggestionIndex >= 0) {
            e.preventDefault();
            suggestions[selectedSuggestionIndex].click();
        } else if (e.key === "Escape") {
            suggestionsBox.style.display = "none";
            selectedSuggestionIndex = -1;
        }
    });
    
    // Dışarı tıklanınca kapat
    document.addEventListener("click", function(e) {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.style.display = "none";
        }
    });
    
    function fetchAutocompleteSuggestions(query) {
        fetch("ai_autocomplete.php?q=" + encodeURIComponent(query))
            .then(res => res.json())
            .then(data => {
                if (data.suggestions && data.suggestions.length > 0) {
                    displaySuggestions(data.suggestions);
                } else {
                    suggestionsBox.style.display = "none";
                }
            })
            .catch(err => {
                console.error("Autocomplete error:", err);
            });
    }
    
    function displaySuggestions(suggestions) {
        currentSuggestions = suggestions;
        selectedSuggestionIndex = -1;
        
        suggestionsBox.innerHTML = "";
        
        suggestions.forEach((suggestion, index) => {
            const div = document.createElement("div");
            div.className = "suggestion-item";
            div.innerHTML = `
                <span class="suggestion-icon">${suggestion.icon}</span>
                <span class="suggestion-text">${suggestion.text}</span>
                <span class="suggestion-type badge bg-secondary">${suggestion.type}</span>
            `;
            
            div.addEventListener("click", function() {
                // İsmi inputa ekle
                const currentText = searchInput.value;
                const words = currentText.split(" ");
                words[words.length - 1] = suggestion.text;
                searchInput.value = words.join(" ") + " ";
                
                suggestionsBox.style.display = "none";
                searchInput.focus();
            });
            
            suggestionsBox.appendChild(div);
        });
        
        suggestionsBox.style.display = "block";
    }
    
    function updateSuggestionSelection(suggestions) {
        suggestions.forEach((item, index) => {
            if (index === selectedSuggestionIndex) {
                item.classList.add("active");
            } else {
                item.classList.remove("active");
            }
        });
    }
    const suggestions = document.getElementById("aiSearchSuggestions");
    
    if (!searchInput || !searchBtn) return;
    
    // Enter tuşu ile arama
    searchInput.addEventListener("keypress", function(e) {
        if (e.key === "Enter") {
            e.preventDefault();
            performAISearch();
        }
    });
    
    // Buton ile arama
    searchBtn.addEventListener("click", performAISearch);
    
    // Modal kapatıldığında focus yönetimi
    const aiModal = document.getElementById("aiSearchModal");
    if (aiModal) {
        aiModal.addEventListener("hidden.bs.modal", function() {
            // Modal kapandığında focus'u search input'a ver
            setTimeout(() => {
                const input = document.getElementById("aiSearchInput");
                if (input) {
                    input.blur(); // Önce blur yap
                }
            }, 100);
        });
    }
    
    // Autocomplete
    searchInput.addEventListener("focus", function() {
        showSuggestions();
    });
    
    searchInput.addEventListener("input", function() {
        const query = this.value.trim();
        if (query.length > 2) {
            filterSuggestions(query);
        } else {
            showSuggestions();
        }
    });
    
    // Dışarı tıklandığında suggestions'ı kapat
    document.addEventListener("click", function(e) {
        if (!searchInput.contains(e.target) && !suggestions.contains(e.target)) {
            suggestions.style.display = "none";
        }
    });
    
    // Sesli arama butonu
    const voiceBtn = document.getElementById("aiVoiceBtn");
    if (voiceBtn) {
        voiceBtn.addEventListener("click", startVoiceRecognition);
    }
});

// Ses Tanıma (Speech Recognition)
let recognition = null;
let isListening = false;

function startVoiceRecognition() {
    const voiceBtn = document.getElementById("aiVoiceBtn");
    
    // Web Speech API kontrolü
    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
        showMicrophoneError('Tarayıcı Desteği Yok', 'Tarayıcınız ses tanımayı desteklemiyor. Lütfen Chrome veya Edge kullanın.');
        return;
    }
    
    if (isListening) {
        // Dinlemeyi durdur
        if (recognition) {
            recognition.stop();
        }
        return;
    }
    
    // Visual feedback - izin bekleniyor
    voiceBtn.classList.add('btn-warning');
    voiceBtn.classList.remove('btn-secondary');
    voiceBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    voiceBtn.title = 'İzin bekleniyor...';
    voiceBtn.disabled = true;
    
    const searchInput = document.getElementById("aiSearchInput");
    const originalPlaceholder = searchInput.placeholder;
    searchInput.placeholder = "⏳ Mikrofon izni bekleniyor...";
    
    // Önce getUserMedia ile mikrofon erişimi iste
    navigator.mediaDevices.getUserMedia({ audio: true })
        .then(function(stream) {
            console.log('✓ Mikrofon erişimi onaylandı');
            // Stream'i kapat (sadece izin almak için kullandık)
            stream.getTracks().forEach(track => track.stop());
            
            // Butonu tekrar aktif et
            voiceBtn.disabled = false;
            voiceBtn.classList.remove('btn-warning');
            voiceBtn.classList.add('btn-secondary');
            voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
            
            // Ses tanımayı başlat
            startRecognitionProcess(voiceBtn);
        })
        .catch(function(error) {
            console.error('✗ Mikrofon erişim hatası:', error.name, error.message);
            
            // Butonu sıfırla
            voiceBtn.disabled = false;
            voiceBtn.classList.remove('btn-warning');
            voiceBtn.classList.add('btn-secondary');
            voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
            searchInput.placeholder = originalPlaceholder;
            
            if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                showMicrophonePermissionModal();
            } else if (error.name === 'NotFoundError') {
                showMicrophoneError('Mikrofon Bulunamadı', 'Bilgisayarınıza mikrofon bağlı olduğundan emin olun.');
            } else {
                showMicrophoneError('Mikrofon Hatası', 'Mikrofon erişimi başarısız: ' + error.message);
            }
        });
}

function startRecognitionProcess(voiceBtn) {
    // Ses tanıma başlat
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    recognition = new SpeechRecognition();
    recognition.lang = 'tr-TR';
    recognition.continuous = false;
    recognition.interimResults = false;
    
    recognition.onstart = function() {
        isListening = true;
        voiceBtn.classList.add('btn-danger');
        voiceBtn.classList.remove('btn-secondary');
        voiceBtn.innerHTML = '<i class="fas fa-microphone-slash"></i>';
        voiceBtn.title = 'Dinlemeyi Durdur';
        
        // Input placeholder değiştir
        const searchInput = document.getElementById("aiSearchInput");
        searchInput.placeholder = "🎤 Dinliyorum...";
    };
    
    recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        const searchInput = document.getElementById("aiSearchInput");
        searchInput.value = transcript;
        
        console.log('🎤 Ses tanındı:', transcript);
        console.log('⏳ 1.5 saniye bekleniyor...');
        
        // Input'u görsel olarak güncelle
        searchInput.placeholder = "✓ Alındı, işleniyor...";
        
        // 1.5 saniye bekle, sonra ara
        setTimeout(() => {
            console.log('✅ Bekleme tamamlandı, arama başlatılıyor');
            performAISearch();
        }, 1500); // 1500ms = 1.5 saniye
    };
    
    recognition.onerror = function(event) {
        console.error('Ses tanıma hatası detayı:', event);
        console.error('Hata kodu:', event.error);
        resetVoiceButton();
        
        if (event.error === 'no-speech') {
            showMicrophoneError('Ses Algılanamadı', 'Lütfen mikrofona yakın konuştuğunuzdan emin olun ve tekrar deneyin.');
        } else if (event.error === 'not-allowed' || event.error === 'permission-denied') {
            showMicrophonePermissionModal();
        } else if (event.error === 'audio-capture') {
            showMicrophoneError('Mikrofon Bulunamadı', 'Bilgisayarınıza mikrofon bağlı olduğundan emin olun.');
        } else if (event.error === 'aborted') {
            // Kullanıcı durdurdu, sessizce geç
            console.log('Ses tanıma kullanıcı tarafından durduruldu');
        } else if (event.error === 'network') {
            showMicrophoneError('Ağ Hatası', 'İnternet bağlantınızı kontrol edin.');
        } else {
            showMicrophoneError('Ses Tanıma Hatası', `Hata: ${event.error}. Konsolu kontrol edin.`);
        }
    };
    
    recognition.onend = function() {
        resetVoiceButton();
    };
    
    // Start ile try-catch
    try {
        recognition.start();
        console.log('Ses tanıma başlatıldı');
    } catch (error) {
        console.error('Recognition.start() hatası:', error);
        resetVoiceButton();
        showMicrophoneError('Başlatma Hatası', 'Mikrofon başlatılamadı: ' + error.message);
    }
}

function resetVoiceButton() {
    isListening = false;
    const voiceBtn = document.getElementById("aiVoiceBtn");
    voiceBtn.classList.remove('btn-danger');
    voiceBtn.classList.add('btn-secondary');
    voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
    voiceBtn.title = 'Sesli Sor';
    
    const searchInput = document.getElementById("aiSearchInput");
    searchInput.placeholder = "AI Asistan'a sorun... (örn: Helmex firması sipariş ortalaması nedir?)";
}

// Ses Sentezi (Text to Speech) - Veritabanı ayarlarından varsayılan değer
let isSpeakerEnabled = <?php echo $tts_enabled ? 'true' : 'false'; ?>;

// localStorage'dan ses ayarlarını yükle (kullanıcı tercihi öncelikli)
document.addEventListener('DOMContentLoaded', function() {
    const autoSpeak = localStorage.getItem('aiAutoSpeak');
    if (autoSpeak !== null) {
        // localStorage varsa onu kullan (kullanıcı manuel değiştirmiş)
        isSpeakerEnabled = autoSpeak === 'true';
    } else {
        // localStorage yoksa veritabanı ayarını kullan ve localStorage'a kaydet
        localStorage.setItem('aiAutoSpeak', isSpeakerEnabled);
    }
    
    // Modal header'daki buton ikonunu güncelle
    const speakerIcon = document.getElementById('speakerIcon');
    const speakerBtn = document.getElementById('aiSpeakerToggle');
    if (speakerIcon && speakerBtn) {
        if (!isSpeakerEnabled) {
            speakerIcon.className = 'fas fa-volume-mute';
            speakerBtn.classList.remove('btn-light');
            speakerBtn.classList.add('btn-danger');
            speakerBtn.title = 'Seslendirmeyi Aç';
        } else {
            speakerIcon.className = 'fas fa-volume-up';
            speakerBtn.classList.remove('btn-danger');
            speakerBtn.classList.add('btn-light');
            speakerBtn.title = 'Seslendirmeyi Kapat';
        }
    }

});

function speakText(text) {
    // Ses kapalıysa çık
    if (!isSpeakerEnabled) {
        console.log('🔇 Ses kapalı, çıkılıyor');
        return;
    }
    
    // TTS türünü kontrol et (browser veya openai)
    const ttsType = localStorage.getItem('aiTTSType') || 'openai'; // Varsayılan OpenAI
    console.log('🎤 speakText çağrıldı');
    console.log('  - TTS Type:', ttsType);
    console.log('  - localStorage aiTTSType:', localStorage.getItem('aiTTSType'));
    console.log('  - Text length:', text.length);
    console.log('  - Text preview:', text.substring(0, 100));
    
    if (ttsType === 'openai') {
        // OpenAI TTS kullan
        console.log('✅ OpenAI TTS seçildi, speakWithOpenAI çağrılıyor...');
        speakWithOpenAI(text);
    } else {
        // Tarayıcı TTS kullan
        console.log('🌐 Browser TTS seçildi, speakWithBrowser çağrılıyor...');
        speakWithBrowser(text);
    }
}

function speakWithOpenAI(text) {
    console.log('🚀 speakWithOpenAI fonksiyonu çağrıldı');
    
    // Ayarlardan OpenAI TTS seçeneklerini al
    const model = localStorage.getItem('aiOpenAITTSModel') || 'tts-1'; // tts-1 veya tts-1-hd
    const voice = localStorage.getItem('aiOpenAITTSVoice') || 'nova'; // alloy, echo, fable, onyx, nova, shimmer
    const speed = parseFloat(localStorage.getItem('aiSpeechRate') || '1.0');
    
    console.log('📋 OpenAI TTS Config:', { model, voice, speed, textLength: text.length });
    console.log('📤 API isteği gönderiliyor...');
    
    // API'ye istek gönder
    fetch('openai_tts.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            text: text,
            model: model,
            voice: voice,
            speed: speed
        })
    })
    .then(response => {
        console.log('📥 OpenAI TTS Response Status:', response.status);
        if (!response.ok) {
            console.error('❌ HTTP error:', response.status, response.statusText);
        }
        return response.json();
    })
    .then(data => {
        console.log('📦 OpenAI TTS Response Data:', data);
        if (data.success && data.audio) {
            // Base64'ten ses dosyasını oluştur
            const audio = new Audio('data:audio/mp3;base64,' + data.audio);
            const volume = parseInt(localStorage.getItem('aiSpeechVolume') || '100') / 100;
            audio.volume = volume;
            console.log('🔊 Ses çalınıyor... (volume:', volume, ')');
            audio.play();
            console.log('✅ OpenAI TTS başarılı');
        } else {
            console.error('❌ OpenAI TTS hatası:', data.error || 'Bilinmeyen hata');
            console.log('⚠️ Tarayıcı TTS\'e geri dönülüyor...');
            // Hata durumunda tarayıcı TTS'e geri dön
            speakWithBrowser(text);
        }
    })
    .catch(error => {
        console.error('❌ OpenAI TTS isteği başarısız:', error);
        console.log('⚠️ Tarayıcı TTS\'e geri dönülüyor...');
        // Hata durumunda tarayıcı TTS'e geri dön
        speakWithBrowser(text);
    });
}

function speakWithBrowser(text) {
    // Web Speech API kontrolü
    if (!('speechSynthesis' in window)) {
        console.log('Tarayıcınız ses sentezini desteklemiyor.');
        return;
    }
    
    // Önceki sesleri durdur
    window.speechSynthesis.cancel();
    
    // Ayarlardan hız ve ses seviyesi al
    const rate = parseFloat(localStorage.getItem('aiSpeechRate') || '1.0');
    const volume = parseInt(localStorage.getItem('aiSpeechVolume') || '100') / 100;
    const selectedVoice = localStorage.getItem('aiSelectedVoice') || '';
    
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'tr-TR';
    utterance.rate = rate;
    utterance.pitch = 1.0;
    utterance.volume = volume;
    
    // En iyi Türkçe sesi seç
    const voices = window.speechSynthesis.getVoices();
    if (voices.length > 0) {
        // Önce kaydedilmiş ses varsa onu kullan
        if (selectedVoice) {
            const savedVoice = voices.find(v => v.name === selectedVoice);
            if (savedVoice) {
                utterance.voice = savedVoice;
            }
        }
        
        // Kaydedilmiş ses yoksa en iyi Türkçe sesi bul
        if (!utterance.voice) {
            // Öncelik sırası: Google/Microsoft premium sesler > Yerleşik sesler
            const turkishVoices = voices.filter(v => v.lang.startsWith('tr'));
            
            // Google Türkçe sesleri (en iyi kalite)
            let bestVoice = turkishVoices.find(v => v.name.includes('Google') && v.name.includes('Türk'));
            
            // Microsoft Edge Türkçe sesleri (ikinci en iyi)
            if (!bestVoice) {
                bestVoice = turkishVoices.find(v => v.name.includes('Emel') || v.name.includes('Tolga'));
            }
            
            // Herhangi bir Türkçe ses
            if (!bestVoice && turkishVoices.length > 0) {
                bestVoice = turkishVoices[0];
            }
            
            if (bestVoice) {
                utterance.voice = bestVoice;
                console.log('Kullanılan ses:', bestVoice.name);
            }
        }
    }
    
    window.speechSynthesis.speak(utterance);
}

function toggleSpeaker() {
    isSpeakerEnabled = !isSpeakerEnabled;
    const icon = document.getElementById("speakerIcon");
    const btn = document.getElementById("aiSpeakerToggle");
    
    // localStorage'a kaydet
    localStorage.setItem('aiAutoSpeak', isSpeakerEnabled);
    
    if (isSpeakerEnabled) {
        icon.className = "fas fa-volume-up";
        btn.classList.remove("btn-danger");
        btn.classList.add("btn-light");
        btn.title = "Seslendirmeyi Kapat";
    } else {
        icon.className = "fas fa-volume-mute";
        btn.classList.remove("btn-light");
        btn.classList.add("btn-danger");
        btn.title = "Seslendirmeyi Aç";
        // Devam eden sesi durdur
        window.speechSynthesis.cancel();
    }
}

// Modal kapandığında sesi durdur
document.addEventListener("DOMContentLoaded", function() {
    const aiModal = document.getElementById("aiSearchModal");
    if (aiModal) {
        aiModal.addEventListener("hidden.bs.modal", function() {
            window.speechSynthesis.cancel();
        });
    }
});


function showSuggestions() {
    const suggestions = document.getElementById("aiSearchSuggestions");
    suggestions.innerHTML = exampleQuestions.map(q => 
        `<div class="ai-suggestion-item" onclick="selectSuggestion('${q}')">
            <i class="fas fa-lightbulb ai-suggestion-icon"></i>
            ${q}
        </div>`
    ).join("");
    suggestions.style.display = "block";
}

function filterSuggestions(query) {
    const suggestions = document.getElementById("aiSearchSuggestions");
    const filtered = exampleQuestions.filter(q => 
        q.toLowerCase().includes(query.toLowerCase())
    );
    
    if (filtered.length > 0) {
        suggestions.innerHTML = filtered.map(q => 
            `<div class="ai-suggestion-item" onclick="selectSuggestion('${q}')">
                <i class="fas fa-lightbulb ai-suggestion-icon"></i>
                ${q}
            </div>`
        ).join("");
        suggestions.style.display = "block";
    } else {
        suggestions.style.display = "none";
    }
}

function selectSuggestion(question) {
    document.getElementById("aiSearchInput").value = question;
    document.getElementById("aiSearchSuggestions").style.display = "none";
    performAISearch();
}

function performAISearch() {
    const question = document.getElementById("aiSearchInput").value.trim();
    
    if (!question) {
        alert("Lütfen bir soru girin");
        return;
    }
    
    // Modal'ı aç
    const modal = new bootstrap.Modal(document.getElementById("aiSearchModal"));
    modal.show();
    
    // Loading göster
    document.getElementById("aiSearchLoading").style.display = "block";
    document.getElementById("aiSearchResult").style.display = "none";
    document.getElementById("aiSearchError").style.display = "none";
    
    // AJAX request
    fetch("ai_chat.php", {
        credentials: 'same-origin',
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ question: question })
    })
    .then(response => {
        // HTTP status kontrolü
        if (!response.ok) {
            if (response.status === 401) {
                throw new Error("Oturum süreniz dolmuş. Lütfen yeniden giriş yapın.");
            }
            // JSON hata mesajını okumaya çalış
            return response.json().then(errData => {
                throw new Error(errData.error || "Sunucu hatası: " + response.status);
            }).catch(e => {
                // JSON parse edilemezse standart hata fırlat
                if (e.message && e.message !== "Unexpected end of JSON input") {
                    throw new Error(e.message);
                }
                throw new Error("Sunucu hatası: " + response.status);
            });
        }
        
        // Content-Type kontrolü
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new Error("Beklenmeyen yanıt formatı. Lütfen sayfayı yenileyip tekrar deneyin.");
        }
        
        return response.json();
    })
    .then(data => {
        document.getElementById("aiSearchLoading").style.display = "none";
        
        if (data.success) {
            currentChatId = data.chat_id;
            displayAIResult(question, data);
        } else {
            showAIError(data.error || "Bir hata oluştu");
        }
    })
    .catch(error => {
        document.getElementById("aiSearchLoading").style.display = "none";
        showAIError(error.message || "Bağlantı hatası oluştu");
        console.error("AI Chat Error:", error);
    });
    
    // Suggestions'ı kapat
    document.getElementById("aiSearchSuggestions").style.display = "none";
}

function displayAIResult(question, data) {
    document.getElementById("aiQuestionText").textContent = question;
    document.getElementById("aiAnswerText").innerHTML = data.answer;
    document.getElementById("aiRecordCount").textContent = data.record_count || (data.data ? data.data.length : 0);
    document.getElementById("aiResponseTime").textContent = data.response_time;
    document.getElementById("aiSqlText").textContent = data.sql;
    
    // Data table - HTML tablo varsa onu kullan (linklerle)
    if (data.html_table && data.html_table.trim()) {
        const dataContainer = document.getElementById("aiDataContainer");
        // Sadece table-responsive içindeki içeriği değiştir, butonları koru
        const tableWrapper = dataContainer.querySelector(".table-responsive");
        if (tableWrapper) {
            tableWrapper.innerHTML = data.html_table;
        }
        dataContainer.style.display = "block";
    } else if (data.data && data.data.length > 0) {
        renderDataTable(data.data);
        document.getElementById("aiDataContainer").style.display = "block";
    } else {
        document.getElementById("aiDataContainer").style.display = "none";
    }
    
    document.getElementById("aiSearchResult").style.display = "block";
    
    // Cevabı seslendir
    const answerText = data.answer.replace(/<[^>]*>/g, ''); // HTML etiketlerini temizle
    if (answerText && answerText.trim().length > 0) {
        speakText(answerText);
    }
}

function renderDataTable(data) {
    const table = document.getElementById("aiDataTable");
    const thead = table.querySelector("thead");
    const tbody = table.querySelector("tbody");
    
    // Clear previous data
    thead.innerHTML = "";
    tbody.innerHTML = "";
    
    if (data.length === 0) return;
    
    // Headers
    const headers = Object.keys(data[0]);
    const headerRow = document.createElement("tr");
    headers.forEach(header => {
        const th = document.createElement("th");
        th.textContent = header;
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);
    
    // Rows
    data.forEach(row => {
        const tr = document.createElement("tr");
        headers.forEach(header => {
            const td = document.createElement("td");
            td.textContent = row[header] || "-";
            tr.appendChild(td);
        });
        tbody.appendChild(tr);
    });
}

function showAIError(message) {
    document.getElementById("aiErrorText").textContent = message;
    document.getElementById("aiSearchError").style.display = "block";
}

function submitAIFeedback(rating) {
    if (!currentChatId) return;
    
    fetch("ai_feedback.php", {
        credentials: 'same-origin',
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            chat_id: currentChatId,
            rating: rating,
            dogru_mu: rating >= 4 ? 1 : 0
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success toast
            const toast = document.createElement("div");
            toast.className = "position-fixed top-0 end-0 p-3";
            toast.style.zIndex = "9999";
            toast.innerHTML = `
                <div class="toast show" role="alert">
                    <div class="toast-header bg-success text-white">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong class="me-auto">Teşekkürler!</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">
                        ${data.message}
                    </div>
                </div>
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    })
    .catch(error => console.error("Feedback error:", error));
}

// Export fonksiyonları
function exportToExcel() {
    const table = document.getElementById("aiDataTable");
    if (!table) return;
    
    // Tabloyu klonla ve linkleri temizle
    const clonedTable = table.cloneNode(true);
    const links = clonedTable.querySelectorAll('a');
    links.forEach(link => {
        link.replaceWith(link.textContent);
    });
    
    // HTML tabloyu Excel formatına çevir
    let html = clonedTable.outerHTML;
    
    // Excel dosyası oluştur
    const blob = new Blob([html], {
        type: 'application/vnd.ms-excel;charset=utf-8;'
    });
    
    // Download link oluştur
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.href = url;
    link.download = "ai_sonuc_" + new Date().getTime() + ".xls";
    link.click();
    URL.revokeObjectURL(url);
    
    // Toast bildirim
    showToast("Excel dosyası indirildi!", "success");
}

function exportToPDF() {
    const table = document.getElementById("aiDataTable");
    const question = document.getElementById("aiQuestionText").textContent;
    const answer = document.getElementById("aiAnswerText").textContent;
    
    if (!table) return;
    
    // Tabloyu klonla ve linkleri temizle
    const clonedTable = table.cloneNode(true);
    const links = clonedTable.querySelectorAll('a');
    links.forEach(link => {
        link.replaceWith(link.textContent.replace(/\s+/g, ' ').trim());
    });
    
    // PDF oluştur
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>AI Sonuç - ${new Date().toLocaleDateString('tr-TR')}</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                h2 { color: #667eea; margin-bottom: 10px; }
                .question { background: #f8f9fa; padding: 15px; border-left: 4px solid #667eea; margin-bottom: 20px; }
                .answer { background: #fff3cd; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #667eea; color: white; }
                tr:nth-child(even) { background-color: #f8f9fa; }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <h2>🤖 AI Asistan Sonucu</h2>
            <div class="question">
                <strong>Soru:</strong> ${question}
            </div>
            <div class="answer">
                <strong>Yanıt:</strong> ${answer}
            </div>
            <h3>Detaylı Veriler</h3>
            ${clonedTable.outerHTML}
            <p style="margin-top: 20px; color: #666; font-size: 12px;">
                Oluşturulma: ${new Date().toLocaleString('tr-TR')}
            </p>
            <button class="no-print" onclick="window.print()" style="margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; border: none; cursor: pointer; border-radius: 5px;">
                PDF Olarak Kaydet
            </button>
        </body>
        </html>
    `);
    printWindow.document.close();
    
    // Toast bildirim
    showToast("PDF önizleme açıldı. Yazdır düğmesine basın.", "info");
}

function showToast(message, type = "success") {
    const bgColor = type === "success" ? "bg-success" : type === "info" ? "bg-info" : type === "warning" ? "bg-warning" : type === "danger" ? "bg-danger" : "bg-primary";
    const icon = type === "success" ? "fa-check-circle" : type === "info" ? "fa-info-circle" : type === "warning" ? "fa-exclamation-triangle" : type === "danger" ? "fa-times-circle" : "fa-bell";
    
    const toast = document.createElement("div");
    toast.className = "position-fixed top-0 end-0 p-3";
    toast.style.zIndex = "9999";
    toast.innerHTML = `
        <div class="toast show" role="alert">
            <div class="toast-header ${bgColor} text-white">
                <i class="fas ${icon} me-2"></i>
                <strong class="me-auto">Bilgi</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// Mikrofon hatası göster
function showMicrophoneError(title, message) {
    showToast(`<strong>${title}</strong><br>${message}`, "danger");
}

// Mikrofon izin modalını göster
function showMicrophonePermissionModal() {
    const modal = new bootstrap.Modal(document.getElementById("microphonePermissionModal"));
    modal.show();
}
</script>
