  <div wire:ignore.self class="modal fade" id="model_has_rolesModal" aria-labelledby="model_has_rolesModalLabel" aria-hidden="true" tabindex="-1"> 
     <div class="modal-dialog modal-lg"> 
     <div class="modal-content"> 
         <div class="modal-header"> 
         <h1 class="modal-title fs-5" id="model_has_rolesModalLabel">Model_has_roles Modal</h1> 
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
                    <th>Role_id</th>
                    <th>Model_type</th>
                    <th>Model_id</th>

                                </tr> 
                            </thead> 
                            <tbody> 
                                @if ($model_has_roless->count() > 0) 
                                    @foreach ($model_has_roless as $datas) 
                                        <tr class="px-4"> 
                                            <td style="text-align: center;"> 
                                                  <button class="btn btn-sm btn-danger text-white" type="button" wire:click="model_has_rolesLOV({{ $datas->idmodel_has_roles }}, 'model_has_roles')"><i class="fa-solid fa-check"></i></button> 
                                            </td> 
                                            <td>{{ $datas->role_id }}</td> 
                                            <td>{{ $datas->model_type }}</td> 
                                            <td>{{ $datas->model_id }}</td> 
                                        </tr> 
                                    @endforeach 
                                @else 
                                    <tr> 
                                        <td colspan=" 2 " style="text-align: center;"><small>Data Tidak di temukan</small></td> 
                                    </tr> 
                                @endif 
                            </tbody> 
                        </table>  
                        </div>  
      <div class=" d-flex justify-content-between align-items-center"> 
          <div> 
           Showing {{ $model_has_roless->firstItem() }} to {{ $model_has_roless->lastItem() }} of {{ $model_has_roless->total() }} results
          </div> 
           <div class="mt-2"> 
               {{ $model_has_roless->links() }} 
           </div>
          </div> 
          </div> 
      </div>
      </div>
  </div> 
