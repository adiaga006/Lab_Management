
             <?php
             include('./constant/connect.php');
             ?>
             
<!-- jQuery - Tải trước các thư viện khác -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap - Bao gồm Popper.js và Bootstrap -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.1/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<!-- Datepicker CSS và JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

<!-- Các thư viện JavaScript khác -->
<script src="assets/js/jquery.slimscroll.js"></script>
<script src="assets/js/sidebarmenu.js"></script>
<script src="assets/js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
<script src="assets/js/lib/sweetalert/sweetalert.min.js"></script>
<script src="assets/js/custom.min.js"></script>

<!-- Các thư viện DataTables -->
<script src="assets/js/lib/datatables/datatables.min.js"></script>
<script src="assets/js/lib/datatables/dataTables.buttons.min.js"></script>
<script src="assets/js/lib/datatables/jszip.min.js"></script>
<script src="assets/js/lib/datatables/pdfmake.min.js"></script>
<script src="assets/js/lib/datatables/vfs_fonts.js"></script>
<script src="assets/js/lib/datatables/buttons.html5.min.js"></script>
<script src="assets/js/lib/datatables/buttons.print.min.js"></script>
<script src="assets/js/lib/datatables/datatables-init.js"></script>

<!-- Calendar và Editor -->
<script src="assets/js/lib/calendar-2/moment.latest.min.js"></script>
<script src="assets/js/lib/calendar-2/semantic.ui.min.js"></script>
<script src="assets/js/lib/calendar-2/prism.min.js"></script>
<script src="assets/js/lib/calendar-2/pignose.calendar.min.js"></script>
<script src="assets/js/lib/calendar-2/pignose.init.js"></script>
<script src="assets/js/lib/html5-editor/wysihtml5-0.3.0.js"></script>
<script src="assets/js/lib/html5-editor/bootstrap-wysihtml5.js"></script>
<script src="assets/js/lib/html5-editor/wysihtml5-init.js"></script>

<!-- Script bổ sung để xóa phần credit (nếu cần) -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const mayuriFooter = document.querySelector("div[style*='Mayuri K']");
        if (mayuriFooter) {
            mayuriFooter.remove();
        }
    });
</script>

<script>
// Các hàm JavaScript khác nếu có
function alphaOnly(event) {
    var key = event.keyCode;
    return ((key >= 65 && key <= 90) || key == 8);
};

function isNumber(evt) {
    var iKeyCode = (evt.which) ? evt.which : evt.keyCode;
    if (iKeyCode != 46 && iKeyCode > 31 && (iKeyCode < 48 || iKeyCode > 57))
        return false;
    return true;
}

function googleTranslateElementInit() {
    new google.translate.TranslateElement({pageLanguage: 'en'}, 'google_translate_element');
}
</script>

<!-- Google Translate -->
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<style>
    .goog-logo-link { display: none !important; }
    .goog-te-gadget { color: transparent; }
    .goog-te-gadget .goog-te-combo { padding: 8px; }
    #google_translate_element { padding-top: 14px; }
</style>

<!-- Footer -->
<footer class="footer" style="text-align: center; padding: 10px; background-color: #e0e0e0;">
    <p>Copyright © 2024 Project Developed by AI Team</p>
</footer>


<script>
    
function onReady(callback) {
    var intervalID = window.setInterval(checkReady, 1000);
    function checkReady() {
        if (document.getElementsByTagName('body')[0] !== undefined) {
            window.clearInterval(intervalID);
            callback.call(this);
        }
    }
}

function show(id, value) {
    document.getElementById(id).style.display = value ? 'block' : 'none';
}

onReady(function () {
    document.querySelector('.footer').style.display = 'block';
    show('page', true);
    show('loading', false);
});


</script>



