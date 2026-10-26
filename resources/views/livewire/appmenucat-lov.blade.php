  <div wire:ignore.self class="modal fade" id="appmenucatModal" aria-labelledby="appmenucatModalLabel" aria-hidden="true" tabindex="-1"> 
     <div class="modal-dialog modal-lg"> 
     <div class="modal-content"> 
         <div class="modal-header"> 
         <h1 class="modal-title fs-5" id="appmenucatModalLabel">Appmenucat Modal</h1> 
         </div> 
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
                    <th>Idappmenucat</th>
                    <th>Appmenucat</th>
                    <th>Urut</th>

                                </tr> 
                            </thead> 
                            <tbody> 
                                @if ($appmenucats->count() > 0) 
                                    @foreach ($appmenucats as $datas) 
                                        <tr class="px-4"> 
                                            <td style="text-align: center;"> 
                                                  <button class="btn btn-sm btn-danger text-white" type="button" wire:click="appmenucatLOV({{ $datas->idappmenucat }}, 'appmenucat')"><i class="fa-solid fa-check"></i></button> 
                                            </td> 
                                            <td>{{ $datas->idappmenucat }}</td> 
                                            <td>{{ $datas->appmenucat }}</td> 
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
           Showing {{ $appmenucats->firstItem() }} to {{ $appmenucats->lastItem() }} of {{ $appmenucats->total() }} results
          </div> 
           <div class="mt-2"> 
               {{ $appmenucats->links() }} 
           </div>
          </div> 
          </div> 
      </div>
      </div>
  </div> 
