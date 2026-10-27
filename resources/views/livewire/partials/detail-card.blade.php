<section class="detail-card">
    <h6 class="detail-card-title mb-0">{{ $title }}</h6>
    <div class="p-2">
        <table class="table table-sm table-borderless detail-table mb-0">
            <tbody>
                @forelse ($rows as $label => $value)
                    <tr>
                        <th scope="row">{{ $label }}</th>
                        <td>
                            @if (is_array($value))
                                @forelse ($value as $item)
                                    <div>{{ $item ?: '-' }}</div>
                                @empty
                                    -
                                @endforelse
                            @else
                                {{ $value ?: '-' }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2">-</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
