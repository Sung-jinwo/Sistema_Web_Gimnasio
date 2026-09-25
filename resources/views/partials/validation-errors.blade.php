
@if($errors->any())
    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800" role="alert">
        <p class="font-semibold"><i class="fas fa-circle-exclamation mr-2"></i>No se pudo completar la operación:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
            @foreach($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif

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
