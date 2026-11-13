<!-- CoreUI and necessary plugins-->
<script src="{{ asset('js/core.js') }}"></script>
<script src="{{ asset('js/simplebar.js') }}"></script>
<script src="{{ asset('asset_offline/js/bootstrap.js') }}"></script>
@include('assets.tooltip')


<x-livewire-alert::flash />


<script>
    document.addEventListener('DOMContentLoaded', function () {
document.addEventListener('keydown', function (event) {
if (event.key === 'Enter') {
const activeModal = document.querySelector('.modal.show');
if (activeModal) {
const confirmButton = activeModal.querySelector('#confirmDeleteButton');
if (confirmButton) {
confirmButton.click();
}
}
}
});
});

    $(document).ready(function() {
        $('input').keydown(function(e) {
            if (e.keyCode == 13) {
                e.preventDefault();
                var inputs = $(this).closest('form').find(':input:visible');
                var nextIndex = inputs.index(this) + 1;
                if (nextIndex < inputs.length) { inputs[nextIndex].focus(); } else { inputs[0].focus(); } } }); });
</script>