<?php
require_once "include/oturum_kontrol.php";

$env_path = __DIR__ . "/.env";
$env_exists = file_exists($env_path);
$api_key_set = false;

if ($env_exists) {
    $env = parse_ini_file($env_path);
    $api_key_set = !empty($env["OPENAI_API_KEY"]) && $env["OPENAI_API_KEY"] !== "your-openai-api-key-here";
}

// API key güncelleme
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_api_key"])) {
    $new_key = trim($_POST["api_key"] ?? "");
    if (!empty($new_key)) {
        file_put_contents($env_path, "OPENAI_API_KEY=$new_key\nOPENAI_MODEL=gpt-4o-mini\n");
        chmod($env_path, 0600);
        header("Location: /index.php?url=ai_ayarlar&success=1");
        exit;
    }
}

// Son analizler
require_once "include/db.php";
$sql = "SELECT 
            a.*,
            r.rapor_adi,
            CONCAT(p.ad, ' ', p.soyad) as kullanici
        FROM ai_analiz_log a
        LEFT JOIN rapor_sablonlari r ON r.id = a.rapor_id
        LEFT JOIN personeller p ON p.id = a.kullanici_id
        WHERE a.firma_id = :firma_id
        ORDER BY a.tarih DESC
        LIMIT 10";
$sth = $conn->prepare($sql);
$sth->bindParam("firma_id", $_SESSION["firma_id"]);
$sth->execute();
$son_analizler = $sth->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row mt-3">
        <div class="col-md-12">
            
            <?php if (isset($_GET["success"])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fa-solid fa-check-circle"></i> API key başarıyla kaydedildi!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- API Key Ayarları -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-robot"></i> AI Analiz Ayarları
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>API Key Durumu:</h6>
                            <?php if ($api_key_set): ?>
                                <div class="alert alert-success">
                                    <i class="fa-solid fa-check-circle"></i> API key tanımlı ve aktif
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fa-solid fa-exclamation-triangle"></i> API key tanımlanmamış
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">OpenAI API Key:</label>
                                    <input type="password" name="api_key" class="form-control" 
                                           placeholder="sk-..." <?= $api_key_set ? 'value="***************************"' : '' ?>>
                                    <small class="text-muted">
                                        <a href="https://platform.openai.com/api-keys" target="_blank">
                                            <i class="fa-solid fa-external-link"></i> API Key Al
                                        </a>
                                    </small>
                                </div>
                                <button type="submit" name="save_api_key" class="btn btn-primary">
                                    <i class="fa-solid fa-save"></i> Kaydet
                                </button>
                            </form>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Özellikler:</h6>
                            <ul>
                                <li>📊 Rapor verilerini analiz eder</li>
                                <li>📈 Trendleri ve örüntüleri bulur</li>
                                <li>💡 İyileştirme önerileri sunar</li>
                                <li>⚡ Hızlı ve doğru sonuçlar</li>
                                <li>🔒 Güvenli ve gizli</li>
                            </ul>
                            
                            <h6 class="mt-3">Maliyet:</h6>
                            <p class="small">
                                <strong>GPT-4o-mini:</strong> ~$0.15 / 1M token<br>
                                Tipik analiz: ~2000 token ≈ $0.0003 (0.01₺)
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ses ve Mikrofon Ayarları -->
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-volume-up"></i> Sesli Asistan Ayarları
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>🔊 Seslendirme Ayarları</h6>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="autoSpeakToggle" checked>
                                <label class="form-check-label" for="autoSpeakToggle">
                                    Yanıtları Otomatik Seslendir
                                </label>
                                <small class="d-block text-muted">
                                    AI yanıtları sesli olarak okunur
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="ttsType" class="form-label">Ses Motoru:</label>
                                <select class="form-select" id="ttsType">
                                    <option value="browser">Tarayıcı TTS (Ücretsiz)</option>
                                    <option value="openai">OpenAI TTS (Premium - $15/1M karakter)</option>
                                </select>
                                <small class="text-muted">
                                    OpenAI TTS daha doğal ve akıcıdır
                                </small>
                            </div>
                            
                            <!-- Tarayıcı TTS Ayarları -->
                            <div id="browserTTSSettings">
                                <div class="mb-3">
                                    <label for="voiceSelect" class="form-label">Tarayıcı Sesi:</label>
                                    <select class="form-select" id="voiceSelect">
                                        <option value="">Varsayılan (Otomatik)</option>
                                    </select>
                                    <small class="text-muted">
                                        <i class="fas fa-star text-warning"></i> Google ve Microsoft sesleri daha kalitelidir
                                    </small>
                                </div>
                            </div>
                            
                            <!-- OpenAI TTS Ayarları -->
                            <div id="openaiTTSSettings" style="display: none;">
                                <div class="mb-3">
                                    <label for="openaiTTSModel" class="form-label">Model:</label>
                                    <select class="form-select" id="openaiTTSModel">
                                        <option value="tts-1">TTS-1 (Hızlı, Ekonomik)</option>
                                        <option value="tts-1-hd">TTS-1-HD (Yüksek Kalite)</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="openaiTTSVoice" class="form-label">Ses Profili:</label>
                                    <select class="form-select" id="openaiTTSVoice">
                                        <option value="alloy">Alloy (Tarafsız)</option>
                                        <option value="echo">Echo (Erkek)</option>
                                        <option value="fable">Fable (Erkek, İngiliz)</option>
                                        <option value="onyx">Onyx (Erkek, Derin)</option>
                                        <option value="nova" selected>Nova (Kadın, Genç)</option>
                                        <option value="shimmer">Shimmer (Kadın, Yumuşak)</option>
                                    </select>
                                    <small class="text-muted">
                                        Nova ve Shimmer genellikle Türkçe'de daha iyi sonuç verir
                                    </small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="speechRate" class="form-label">Konuşma Hızı: <span id="rateValue">1.0</span>x</label>
                                <input type="range" class="form-range" id="speechRate" min="0.5" max="2" step="0.1" value="1.0">
                            </div>
                            
                            <div class="mb-3">
                                <label for="speechVolume" class="form-label">Ses Seviyesi: <span id="volumeValue">100</span>%</label>
                                <input type="range" class="form-range" id="speechVolume" min="0" max="100" step="5" value="100">
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary" onclick="saveVoiceSettings()">
                                    <i class="fas fa-save"></i> Ayarları Kaydet
                                </button>
                                <button class="btn btn-sm btn-success" onclick="testSpeech()">
                                    <i class="fas fa-play"></i> Test Et
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>🎤 Mikrofon Ayarları</h6>
                            <div class="alert alert-info">
                                <strong>Mikrofon İzni:</strong>
                                <div id="micStatus" class="mt-2">
                                    <i class="fas fa-spinner fa-spin"></i> Kontrol ediliyor...
                                </div>
                            </div>
                            
                            <button class="btn btn-sm btn-warning" onclick="checkMicrophonePermission()">
                                <i class="fas fa-sync"></i> Mikrofon Durumunu Kontrol Et
                            </button>
                            
                            <button class="btn btn-sm btn-success ms-2" onclick="testMicrophone()">
                                <i class="fas fa-microphone"></i> Mikrofonu Test Et
                            </button>
                            
                            <div class="mt-3">
                                <small class="text-muted">
                                    <strong>Not:</strong> Mikrofon erişimi için tarayıcınızdan izin vermeniz gerekir.
                                    Chrome: Adres çubuğu → Kilit ikonu → İzinler
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Son Analizler -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-history"></i> Son AI Analizleri
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($son_analizler)): ?>
                        <div class="alert alert-info">
                            <i class="fa-solid fa-info-circle"></i> Henüz AI analizi yapılmamış.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Rapor</th>
                                        <th>Kullanıcı</th>
                                        <th>Kayıt</th>
                                        <th>Özet</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($son_analizler as $analiz): ?>
                                    <tr>
                                        <td><?= date('d.m.Y H:i', strtotime($analiz['tarih'])) ?></td>
                                        <td><?= htmlspecialchars($analiz['rapor_adi']) ?></td>
                                        <td><?= htmlspecialchars($analiz['kullanici']) ?></td>
                                        <td><?= $analiz['kayit_sayisi'] ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" 
                                                    onclick="showAnalysis(<?= $analiz['id'] ?>)">
                                                <i class="fa-solid fa-eye"></i> Görüntüle
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- Analiz Görüntüleme Modal -->
<div class="modal fade" id="analysisViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">AI Analiz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="analysisContent">
                <!-- İçerik buraya gelecek -->
            </div>
        </div>
    </div>
</div>

<script>
const analizler = <?= json_encode($son_analizler, JSON_UNESCAPED_UNICODE) ?>;

function showAnalysis(id) {
    const analiz = analizler.find(a => a.id == id);
    if (analiz) {
        document.getElementById('analysisContent').innerHTML = 
            '<pre class="p-3 bg-light rounded">' + analiz.analiz + '</pre>';
        new bootstrap.Modal(document.getElementById('analysisViewModal')).show();
    }
}

// Ses ayarlarını localStorage'dan yükle
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 AI Ayarları sayfası yüklendi');
    
    // Seslendirme ayarı
    const autoSpeak = localStorage.getItem('aiAutoSpeak');
    if (autoSpeak !== null) {
        document.getElementById('autoSpeakToggle').checked = autoSpeak === 'true';
    }
    
    // TTS türü
    const ttsType = localStorage.getItem('aiTTSType') || 'openai'; // Varsayılan OpenAI
    console.log('📦 localStorage aiTTSType:', ttsType);
    const ttsTypeSelect = document.getElementById('ttsType');
    if (ttsTypeSelect) {
        ttsTypeSelect.value = ttsType;
        console.log('✅ TTS select değeri ayarlandı:', ttsTypeSelect.value);
    } else {
        console.error('❌ ttsType select elementi bulunamadı!');
    }
    
    // Element'lerin varlığını kontrol et
    const browserSettings = document.getElementById('browserTTSSettings');
    const openaiSettings = document.getElementById('openaiTTSSettings');
    console.log('🔍 Element kontrolü:');
    console.log('  - browserTTSSettings:', browserSettings ? 'VAR ✓' : 'YOK ✗');
    console.log('  - openaiTTSSettings:', openaiSettings ? 'VAR ✓' : 'YOK ✗');
    
    // TTS ayarlarını hemen göster
    console.log('🎯 toggleTTSSettings çağrılıyor, tür:', ttsType);
    toggleTTSSettings(ttsType);
    
    // OpenAI TTS model
    const openaiModel = localStorage.getItem('aiOpenAITTSModel') || 'tts-1';
    document.getElementById('openaiTTSModel').value = openaiModel;
    
    // OpenAI TTS voice
    const openaiVoice = localStorage.getItem('aiOpenAITTSVoice') || 'nova';
    document.getElementById('openaiTTSVoice').value = openaiVoice;
    
    // Konuşma hızı
    const speechRate = localStorage.getItem('aiSpeechRate') || '1.0';
    document.getElementById('speechRate').value = speechRate;
    document.getElementById('rateValue').textContent = speechRate;
    
    // Ses seviyesi
    const speechVolume = localStorage.getItem('aiSpeechVolume') || '100';
    document.getElementById('speechVolume').value = speechVolume;
    document.getElementById('volumeValue').textContent = speechVolume;
    
    // Ses listesini yükle
    loadVoices();
    if (window.speechSynthesis.onvoiceschanged !== undefined) {
        window.speechSynthesis.onvoiceschanged = loadVoices;
    }
    
    // Event listeners
    document.getElementById('autoSpeakToggle').addEventListener('change', function() {
        localStorage.setItem('aiAutoSpeak', this.checked);
        showToast(this.checked ? 'Seslendirme açıldı' : 'Seslendirme kapatıldı', 'success');
    });
    
    document.getElementById('ttsType').addEventListener('change', function() {
        const newType = this.value;
        console.log('🔄 Ses motoru değiştiriliyor...', 'Eski:', localStorage.getItem('aiTTSType'), 'Yeni:', newType);
        localStorage.setItem('aiTTSType', newType);
        console.log('💾 localStorage aiTTSType:', localStorage.getItem('aiTTSType'));
        toggleTTSSettings(newType);
        
        const message = newType === 'openai' ? 'OpenAI TTS Aktif 🚀' : 'Tarayıcı TTS Aktif 🌐';
        showToast(message, 'success');
    });
    
    document.getElementById('openaiTTSModel').addEventListener('change', function() {
        localStorage.setItem('aiOpenAITTSModel', this.value);
        showToast('Model seçimi kaydedildi', 'success');
    });
    
    document.getElementById('openaiTTSVoice').addEventListener('change', function() {
        localStorage.setItem('aiOpenAITTSVoice', this.value);
        showToast('Ses profili kaydedildi', 'success');
    });
    
    document.getElementById('voiceSelect').addEventListener('change', function() {
        localStorage.setItem('aiSelectedVoice', this.value);
        showToast('Ses seçimi kaydedildi', 'success');
    });
    
    document.getElementById('speechRate').addEventListener('input', function() {
        document.getElementById('rateValue').textContent = this.value;
        localStorage.setItem('aiSpeechRate', this.value);
    });
    
    document.getElementById('speechVolume').addEventListener('input', function() {
        document.getElementById('volumeValue').textContent = this.value;
        localStorage.setItem('aiSpeechVolume', this.value);
    });
    
    // Mikrofon durumunu kontrol et
    checkMicrophonePermission();
});

function toggleTTSSettings(ttsType) {
    const browserSettings = document.getElementById('browserTTSSettings');
    const openaiSettings = document.getElementById('openaiTTSSettings');
    
    console.log('🔧 toggleTTSSettings çağrıldı');
    console.log('  - ttsType:', ttsType);
    console.log('  - browserSettings:', browserSettings);
    console.log('  - openaiSettings:', openaiSettings);
    
    if (!browserSettings || !openaiSettings) {
        console.error('❌ TTS ayar element(ler)i bulunamadı!');
        console.error('  - browserSettings:', !!browserSettings);
        console.error('  - openaiSettings:', !!openaiSettings);
        return;
    }
    
    if (ttsType === 'openai') {
        browserSettings.style.display = 'none';
        openaiSettings.style.display = 'block';
        console.log('✅ OpenAI TTS ayarları gösteriliyor (display: block)');
        console.log('  - browserSettings.style.display:', browserSettings.style.display);
        console.log('  - openaiSettings.style.display:', openaiSettings.style.display);
    } else {
        browserSettings.style.display = 'block';
        openaiSettings.style.display = 'none';
        console.log('✅ Tarayıcı TTS ayarları gösteriliyor (display: block)');
        console.log('  - browserSettings.style.display:', browserSettings.style.display);
        console.log('  - openaiSettings.style.display:', openaiSettings.style.display);
    }
}

function saveVoiceSettings() {
    // Tüm ayarlar zaten otomatik kaydediliyor, sadece onay mesajı göster
    const ttsType = localStorage.getItem('aiTTSType') || 'browser';
    const autoSpeak = document.getElementById('autoSpeakToggle').checked;
    
    showToast('Ses ayarları kaydedildi! TTS: ' + (ttsType === 'openai' ? 'OpenAI' : 'Tarayıcı'), 'success');
    
    // Ayarları konsola yazdır
    console.log('Kaydedilen Ayarlar:', {
        ttsType: ttsType,
        autoSpeak: autoSpeak,
        model: localStorage.getItem('aiOpenAITTSModel'),
        voice: localStorage.getItem('aiOpenAITTSVoice'),
        rate: localStorage.getItem('aiSpeechRate'),
        volume: localStorage.getItem('aiSpeechVolume')
    });
}

function loadVoices() {
    const voiceSelect = document.getElementById('voiceSelect');
    const voices = window.speechSynthesis.getVoices();
    const savedVoice = localStorage.getItem('aiSelectedVoice');
    
    // Mevcut seçenekleri temizle (varsayılan hariç)
    while (voiceSelect.options.length > 1) {
        voiceSelect.remove(1);
    }
    
    // Türkçe sesleri filtrele ve sırala
    const turkishVoices = voices.filter(v => v.lang.startsWith('tr'));
    
    // Sıralama: Google > Microsoft > Diğerleri
    turkishVoices.sort((a, b) => {
        const aScore = getVoiceQualityScore(a);
        const bScore = getVoiceQualityScore(b);
        return bScore - aScore;
    });
    
    turkishVoices.forEach(voice => {
        const option = document.createElement('option');
        option.value = voice.name;
        
        // Kalite işareti ekle
        let quality = '';
        if (voice.name.includes('Google')) quality = '⭐⭐⭐ ';
        else if (voice.name.includes('Microsoft') || voice.name.includes('Emel') || voice.name.includes('Tolga')) quality = '⭐⭐ ';
        
        option.textContent = `${quality}${voice.name} (${voice.lang})`;
        
        if (voice.name === savedVoice) {
            option.selected = true;
        }
        
        voiceSelect.appendChild(option);
    });
}

function getVoiceQualityScore(voice) {
    if (voice.name.includes('Google')) return 3;
    if (voice.name.includes('Microsoft') || voice.name.includes('Emel') || voice.name.includes('Tolga')) return 2;
    return 1;
}

function testSpeech() {
    // Seçili dropdown değerini al (localStorage değil!)
    const ttsTypeSelect = document.getElementById('ttsType');
    const ttsType = ttsTypeSelect ? ttsTypeSelect.value : (localStorage.getItem('aiTTSType') || 'openai');
    const testText = 'Merhaba, ben AI asistanınızım. Ses ayarlarınız test ediliyor. Bu sesi beğenirseniz kaydedin.';
    
    console.log('🧪 Test başlatılıyor, TTS türü:', ttsType);
    
    if (ttsType === 'openai') {
        // OpenAI TTS ile test
        const model = document.getElementById('openaiTTSModel').value;
        const voice = document.getElementById('openaiTTSVoice').value;
        const speed = parseFloat(document.getElementById('speechRate').value);
        const volume = parseInt(document.getElementById('speechVolume').value) / 100;
        
        console.log('🚀 OpenAI TTS test parametreleri:', { model, voice, speed, volume });
        showToast('OpenAI TTS test ediliyor...', 'info');
        
        fetch('openai_tts.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                text: testText,
                model: model,
                voice: voice,
                speed: speed
            })
        })
        .then(response => {
            console.log('📥 OpenAI test yanıtı:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📦 OpenAI test data:', data);
            if (data.success && data.audio) {
                const audio = new Audio('data:audio/mp3;base64,' + data.audio);
                audio.volume = volume;
                audio.play();
                console.log('✅ OpenAI TTS testi başarılı');
                showToast('OpenAI TTS testi başarılı! 🎉', 'success');
            } else {
                console.error('❌ OpenAI TTS hatası:', data.error);
                showToast('Hata: ' + (data.error || 'Bilinmeyen hata'), 'danger');
            }
        })
        .catch(error => {
            console.error('❌ İstek hatası:', error);
            showToast('İstek hatası: ' + error, 'danger');
        });
    } else {
        // Tarayıcı TTS ile test
        console.log('🌐 Tarayıcı TTS test ediliyor...');
        const rate = parseFloat(document.getElementById('speechRate').value);
        const volume = parseInt(document.getElementById('speechVolume').value) / 100;
        const selectedVoiceName = document.getElementById('voiceSelect').value;
        
        if ('speechSynthesis' in window) {
            const utterance = new SpeechSynthesisUtterance(testText);
            utterance.lang = 'tr-TR';
            utterance.rate = rate;
            utterance.volume = volume;
            utterance.pitch = 1.0;
            
            // Seçili sesi kullan
            if (selectedVoiceName) {
                const voices = window.speechSynthesis.getVoices();
                const selectedVoice = voices.find(v => v.name === selectedVoiceName);
                if (selectedVoice) {
                    utterance.voice = selectedVoice;
                }
            }
            
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(utterance);
        } else {
            alert('Tarayıcınız ses sentezini desteklemiyor.');
        }
    }
}

function checkMicrophonePermission() {
    const statusDiv = document.getElementById('micStatus');
    
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        statusDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> Tarayıcınız mikrofonu desteklemiyor</span>';
        return;
    }
    
    // Permissions API ile kontrol
    if (navigator.permissions && navigator.permissions.query) {
        navigator.permissions.query({ name: 'microphone' }).then(function(permissionStatus) {
            updateMicStatus(permissionStatus.state);
            
            permissionStatus.onchange = function() {
                updateMicStatus(this.state);
            };
        }).catch(function() {
            // Permissions API desteklenmiyorsa direkt getUserMedia dene
            testMicrophoneAccess();
        });
    } else {
        testMicrophoneAccess();
    }
}

function updateMicStatus(state) {
    const statusDiv = document.getElementById('micStatus');
    
    if (state === 'granted') {
        statusDiv.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> İzin verildi - Mikrofon kullanılabilir</span>';
    } else if (state === 'denied') {
        statusDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> İzin reddedildi - Tarayıcı ayarlarından izin verin</span>';
    } else {
        statusDiv.innerHTML = '<span class="text-warning"><i class="fas fa-question-circle"></i> İzin bekleniyor - Mikrofon butonuna basın</span>';
    }
}

function testMicrophoneAccess() {
    navigator.mediaDevices.getUserMedia({ audio: true })
        .then(function(stream) {
            updateMicStatus('granted');
            stream.getTracks().forEach(track => track.stop());
        })
        .catch(function(error) {
            if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                updateMicStatus('denied');
            } else {
                updateMicStatus('prompt');
            }
        });
}

function testMicrophone() {
    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
        alert('Tarayıcınız ses tanımayı desteklemiyor. Lütfen Chrome veya Edge kullanın.');
        return;
    }
    
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const recognition = new SpeechRecognition();
    recognition.lang = 'tr-TR';
    recognition.continuous = false;
    
    recognition.onstart = function() {
        showToast('Mikrofon aktif - Bir şeyler söyleyin...', 'info');
    };
    
    recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        showToast('Algılanan: "' + transcript + '"', 'success');
    };
    
    recognition.onerror = function(event) {
        if (event.error === 'not-allowed') {
            showToast('Mikrofon izni reddedildi!', 'danger');
        } else {
            showToast('Hata: ' + event.error, 'danger');
        }
    };
    
    recognition.start();
}

function showToast(message, type = 'success') {
    const bgColor = type === 'success' ? 'bg-success' : type === 'info' ? 'bg-info' : type === 'danger' ? 'bg-danger' : 'bg-primary';
    const icon = type === 'success' ? 'fa-check-circle' : type === 'info' ? 'fa-info-circle' : type === 'danger' ? 'fa-times-circle' : 'fa-bell';
    
    const toast = document.createElement('div');
    toast.className = 'position-fixed top-0 end-0 p-3';
    toast.style.zIndex = '9999';
    toast.innerHTML = `
        <div class="toast show" role="alert">
            <div class="toast-header ${bgColor} text-white">
                <i class="fas ${icon} me-2"></i>
                <strong class="me-auto">Bildirim</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">${message}</div>
        </div>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>
