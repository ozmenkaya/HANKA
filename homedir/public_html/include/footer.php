<footer class="footer">
    <div class="container-fluid">
        <div class="row">
        <div class="col-2">
        <div class="bg-primary text-white text-center breaking-caret py-1 rounded fw-bold">
            <i class="fa-regular fa-newspaper"></i>
            <span class="d-none d-md-inline-block">YENİ ÖZELLİKLER</span>
        </div>
    </div>

    <div class="col-10">
        <div class="breaking-box pt-2 pb-1">
            <!--marque-->
            <marquee behavior="scroll" direction="left" onmouseover="this.stop();" onmouseleave="this.start();">
                <a class="h6 fw-normal" href="/index.php?url=birim">
                    <span class="position-relative mx-2 badge bg-primary rounded">
                        <i class="fa-solid fa-ruler-vertical"></i> BİRİM EKLEME
                    </span> 
                    Firma Ait Birim Ekleme Özelliği(Tıklayınız)
                </a>
                <a class="h6 fw-normal" href="/index.php?url=makina">
                    <span class="position-relative mx-2 badge bg-primary rounded">
                        <i class="fa-solid fa-gears"></i> MAKİNA ÜRETİM
                    </span> 
                    Makina Üretimde Ayar Süresi Olsun Olmasını Ayarlabilirsiniz. (Tıklayınız)
                </a>
                <a class="h6" href="/index.php?url=geri_bildirim">
                    <span class="position-relative mx-2 badge bg-primary rounded">
                        <i class="fa-solid fa-bug"></i> HATA
                    </span> 
                    Hataları Bularak Bize Bildirebilirsiniz. Geliştirmemize Yardımcı Olunuz. (Tıklayınız)
                </a>
            </marquee>
        </div>
    </div>
        </div>
    </div>
</footer><script>
function openAIChat(event) {
    event.preventDefault();
    window.open("/ai_chat.php", "AI Chat", "width=800,height=600,resizable=yes,scrollbars=yes");
    return false;
}
</script>
