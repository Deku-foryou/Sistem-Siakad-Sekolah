  <div wire:ignore.self class="modal fade" id="docscatModal" aria-labelledby="docscatModalLabel" aria-hidden="true" tabindex="-1"> 
     <div class="modal-dialog modal-lg"> 
     <div class="modal-content"> 
         <div class="modal-header"> 
         <h1 class="modal-title fs-5" id="docscatModalLabel">Docscat Modal</h1> 
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
                    <th>Iddocscat</th>
                    <th>Docscat</th>
                    <th>Urut</th>

                                </tr> 
                            </thead> 
                            <tbody> 
                                @if ($docscats->count() > 0) 
                                    @foreach ($docscats as $datas) 
                                        <tr class="px-4"> 
                                            <td style="text-align: center;"> 
                                                  <button class="btn btn-sm btn-danger text-white" type="button" wire:click="docscatLOV({{ $datas->iddocscat }}, 'docscat')"><i class="fa-solid fa-check"></i></button> 
                                            </td> 
                                            <td>{{ $datas->iddocscat }}</td> 
                                            <td>{{ $datas->docscat }}</td> 
                                            <td>{{ $datas->urut }}</td> 
                                        </tr> 
                                    @endforeach 
                                @else 
                                    <tr> 
                                        <td colspan=" 4 " style="text-align: center;"><small>Data Tidak di temukan</small></td> 
                                    </tr> 
                                @endif 
                            </tbody> 
                        </table>  
                        </div>  
      <div class=" d-flex justify-content-between align-items-center"> 
          <div> 
           Showing {{ $docscats->firstItem() }} to {{ $docscats->lastItem() }}
          </div> 
           <div class="mt-2"> 
               {{ $docscats->links() }} 
           </div>
          </div> 
          </div> 
      </div>
      </div>
  </div> 
