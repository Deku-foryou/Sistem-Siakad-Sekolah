  <div wire:ignore.self class="modal fade" id="appmenuModal" aria-labelledby="appmenuModalLabel" aria-hidden="true" tabindex="-1"> 
     <div class="modal-dialog modal-lg"> 
     <div class="modal-content"> 
         <div class="modal-header"> 
         <h1 class="modal-title fs-5" id="appmenuModalLabel">Appmenu Modal</h1> 
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
                    <th>Idappmenu</th>
                    <th>Appmenu</th>
                    <th>Url</th>
                    <th>Created_by</th>
                    <th>Updated_by</th>

                                </tr> 
                            </thead> 
                            <tbody> 
                                @if ($appmenus->count() > 0) 
                                    @foreach ($appmenus as $datas) 
                                        <tr class="px-4"> 
                                            <td style="text-align: center;"> 
                                                  <button class="btn btn-sm btn-danger text-white" type="button" wire:click="appmenuLOV({{ $datas->idappmenu }}, 'appmenu')"><i class="fa-solid fa-check"></i></button> 
                                            </td> 
                                            <td>{{ $datas->idappmenu }}</td> 
                                            <td>{{ $datas->appmenu }}</td> 
                                            <td>{{ $datas->url }}</td> 
                                            <td>{{ $datas->created_by }}</td> 
                                            <td>{{ $datas->updated_by }}</td> 
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
           Showing {{ $appmenus->firstItem() }} to {{ $appmenus->lastItem() }} of {{ $appmenus->total() }} results
          </div> 
           <div class="mt-2"> 
               {{ $appmenus->links() }} 
           </div>
          </div> 
          </div> 
      </div>
      </div>
  </div> 
