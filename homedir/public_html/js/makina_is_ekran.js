const simdiki_saat = setInterval(() => {
    $("#simdiki-saat").text(mevcut_saat());
}, 1000);

$(function(){

    

    //Molayı Başlat
    $("#mola-baslat").click(function(){
        if(confirm("Molayı Başlamak İstediğinize Emin Misiniz?")){
            $("#mola-modal").modal('show');
            const baslatma_tarih = new Date();
            $("#mola_baslatma_tarih").val(formatDate(baslatma_tarih));

            let mola_gecen_sure_interval = setInterval(()=>{
                $("#mola-gecen-sure").text(iki_tarih_arasindaki(baslatma_tarih, new Date()))
            }, 1000);
        }
    });

    //Yemek Mola Başlat
    $("#yemek-mola-baslat").click(function(){
        if(confirm("Yemek Molasını Başlatmak İstediğinize Emin Misiniz?")){
            $("#yemek-mola-modal").modal('show');
            const baslatma_tarih = new Date();
            $("#yemek_mola_baslatma_tarih").val(formatDate(baslatma_tarih));

            let mola_gecen_sure_interval = setInterval(()=>{
                $("#yemek-mola-gecen-sure").text(iki_tarih_arasindaki(baslatma_tarih, new Date()))
            }, 1000);
        }
    });

    //Toplantı Başlat
    $("#toplanti-baslat").click(function(){
        if(confirm("Toplantıyı Başlatmak İstediğinize Emin Misiniz?")){
            $("#toplanti-modal").modal('show');
            const baslatma_tarih = new Date();
            $("#toplanti_baslatma_tarih").val(formatDate(baslatma_tarih));

            let toplanti_gecen_sure_interval = setInterval(()=>{
                $("#toplanti-gecen-sure").text(iki_tarih_arasindaki(baslatma_tarih, new Date()))
            }, 1000);
        }
    });

    //Paydos 
    $("#paydos-baslat").click(function(){
        if(confirm("Paydos Yapmak İstediğinize Emin Misiniz?")){
            $("#paydos-modal").modal('show');
        }
    });

    //Paydos Focus İnput
    $('#paydos-modal').on('shown.bs.modal', function() {
        $('#uretilen_adet').focus();
    });

    //Devret 
    $("#devret-baslat").click(function(){
        if(confirm("Başka Makinaya Aktarmak İstediğinize Emin Misiniz?")){
            $("#devret-modal").modal('show');
        }
    });

    //Değiştir 
    $("#degistir-baslat").click(function(){
        if(confirm("İşi Değiştirmek İstediğinize Emin Misiniz?")){
            $("#degistir-modal").modal('show');
        }
    });

    //Değiştir Focus İnput
    $('#degistir-modal').on('shown.bs.modal', function() {
        $('#degistirme_sebebi').focus();
    });


    //Yetkili 
    $("#yetkili-baslat").click(function(){
        if(confirm("Yetkili Çağırmak İstediğinize Emin Misiniz?")){
            $("#yetkili-cagirma-modal").modal('show');
        }
    });

    //Arıza 
    $("#ariza-baslat").click(function(){
        if(confirm("Arızayı Başlatmak İstediğinize Emin Misiniz?")){
            $("#ariza-modal").modal('show');
            const baslatma_tarih = new Date();
            $("#ariza_baslatma_tarih").val(formatDate(baslatma_tarih));

            let ariza_gecen_sure_interval = setInterval(()=>{
                $("#ariza-gecen-sure").text(iki_tarih_arasindaki(baslatma_tarih, new Date()))
            }, 1000);
        }
    });

    //Aktar 
    $("#aktar-baslat").click(function(){
        if(confirm("İş Aktarmak İstediğinize Emin Misiniz?")){
            $("#aktar-modal").modal('show');
        }
    });


});

function mevcut_saat()
{
    const today     = new Date();
    const saat      = today.getHours() < 10 ? "0" + today.getHours() : today.getHours();
    const dakika    = today.getMinutes() < 10 ? "0" + today.getMinutes() : today.getMinutes();
    const saniye    = today.getSeconds() < 10 ? "0" + today.getSeconds() : today.getSeconds();
    return saat + ":" + dakika + ":" + saniye;
}

function iki_tarih_arasindaki(baslatma, bitirme)
{
    const ilkTarih      = new Date(baslatma);
    const ikinciTarih   = new Date(bitirme);

    const fark = ikinciTarih - ilkTarih;

    let farkSaniye  = Math.floor(fark / 1000) % 60;
    let farkDakika  = Math.floor(fark / (1000 * 60)) % 60;
    let farkSaat    = Math.floor(fark / (1000 * 60 * 60));

    if(farkSaat == 0)           farkSaat = "00" 
    else if(farkSaat < 10)      farkSaat = `0${farkSaat}`;

    if(farkDakika == 0)         farkDakika = "00";
    else if(farkDakika < 10)    farkDakika = `0${farkDakika}`;

    if(farkSaniye == 0)         farkSaniye = "00";
    else if(farkSaniye < 10)    farkSaniye = `0${farkSaniye}`;


    return `${farkSaat}:${farkDakika}:${farkSaniye}`;
}



function padTo2Digits(num) {
    return num.toString().padStart(2, '0');
}

function formatDate(date) {
    return (
        [
            date.getFullYear(),
            padTo2Digits(date.getMonth() + 1),
            padTo2Digits(date.getDate()),
        ].join('-') +
        ' ' +
        [
            padTo2Digits(date.getHours()),
            padTo2Digits(date.getMinutes()),
            padTo2Digits(date.getSeconds()),
        ].join(':')
    );
}
