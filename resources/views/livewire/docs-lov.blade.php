  <div wire:ignore.self class="modal fade" id="docsModal" aria-labelledby="docsModalLabel" aria-hidden="true" tabindex="-1"> 
     <div class="modal-dialog modal-lg"> 
     <div class="modal-content"> 
         <div class="modal-header"> 
         <h1 class="modal-title fs-5" id="docsModalLabel">Docs Modal</h1> 
         </div> 
       
       
   {{-- Loader --}}    
     <div wire:loading   
       wire:target="searchlov"   
        class="loading-spinner">  
     <div class="loader"></div>  
    </div>    
     {{-- Loader --}}   
       
       
         <div class="modal-body"> 
             <div class="row pb-2"> 
                <div class="col-6"> 
                 <div>Search</div> 
                  <input type="text" class="form-control" placeholder="Cari data .." wire:model.live.debounce.300ms="searchlov"> 
                 </div> 
             </div>
                        <div  class="table-responsive" > 
                        <table  class="table" style="width:100%"> 
                            <thead> 
                                <tr> 
                                    <th style="text-align: center;">Action</th> 
                    <th>Iddocs</th>
                    <th>Title</th>
                    <th>Content</th>
                    <th>Urut</th>
                    <th>Docscat_iddocscat</th>

                                </tr> 
                            </thead> 
                            <tbody> 
                                @if ($docss->count() > 0) 
                                    @foreach ($docss as $datas) 
                                        <tr class="px-4"> 
                                            <td style="text-align: center;"> 
                                                  <button class="btn btn-sm btn-danger text-white" type="button" wire:click="docsLOV({{ $datas->iddocs }}, 'docs')"><i class="fa-solid fa-check"></i></button> 
                                            </td> 
                                            <td>{{ $datas->iddocs }}</td> 
                                            <td>{{ $datas->title }}</td> 
                                            <td>{{ $datas->content }}</td> 
                                            <td>{{ $datas->urut }}</td> 
                                            <td>{{ $datas->docscat_iddocscat }}</td> 
                                        </tr> 
                                    @endforeach 
                                @else 
                                    <tr> 
                                        <td colspan=" 6 " style="text-align: center;"><small>Data Tidak di temukan</small></td> 
                                    </tr> 
                                @endif 
                            </tbody> 
                        </table>  
                        </div>  
      <div class=" d-flex justify-content-between align-items-center"> 
          <div> 
           Showing {{ $docss->firstItem() }} to {{ $docss->lastItem() }}
          </div> 
           <div class="mt-2"> 
               {{ $docss->links() }} 
           </div>
          </div> 
          </div> 
      </div>
      </div>
  </div> 
