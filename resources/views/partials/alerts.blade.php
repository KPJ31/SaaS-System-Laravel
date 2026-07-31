@if(session('success') || session('error'))
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            Swal.fire({
                icon: @json(session('success') ? 'success' : 'error'),
                title: @json(session('success') ? 'Success' : 'Error'),
                text: @json(session('success') ?: session('error')),
                confirmButtonColor: '#7C3AED'
            });
        });
    </script>
@endif
