

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            @if(count($headers) > 0)
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        @foreach($headers as $header)
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">
                                {{ $header }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>