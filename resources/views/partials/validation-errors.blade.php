
@if(session('success'))
    <script>
        document.addEventListener('alpine:initialized', () => {
            window.notify.success('{{ session('success') }}');
        });
    </script>
@endif

@if(session('error'))
    <script>
        document.addEventListener('alpine:initialized', () => {
            window.notify.error('{{ session('error') }}');
        });
    </script>
@endif

@if(session('warning'))
    <script>
        document.addEventListener('alpine:initialized', () => {
            window.notify.warning('{{ session('warning') }}');
        });
    </script>
@endif

@if(session('info'))
    <script>
        document.addEventListener('alpine:initialized', () => {
            window.notify.info('{{ session('info') }}');
        });
    </script>
@endif