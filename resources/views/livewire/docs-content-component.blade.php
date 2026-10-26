<div>
    <div class=" container-fluid p-3 pt-3">
        {{-- <div class="mb-4">
            <div class="text-secondary">Dokumentasi Website</div>
            <h3 class="fw-bold">{{ $getDocs->title }}</h3>
        </div> --}}

        <div class="">

            <div class="card p-2">
                <iframe src="{{ asset('storage/tutorial-website/' . $getDocs->content) }}" frameborder="0" width="100%"
                    style="height: 90vh"></iframe>
            </div>

            {{-- {!! $getDocs->content !!} --}}
        </div>

    </div>
</div>
