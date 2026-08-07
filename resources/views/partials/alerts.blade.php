@php
    $flashType = session('success') ? 'success' : (session('error') ? 'error' : (session('warning') ? 'warning' : (session('info') ? 'info' : null)));
    $flashMessage = $flashType ? session($flashType) : null;
@endphp

@if($flashType && $flashMessage)
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            window.ElevanixUI?.toast(@json($flashType), @json($flashMessage));
        });
    </script>
@endif
