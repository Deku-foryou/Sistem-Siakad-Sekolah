  <div wire:ignore.self class="modal fade" id="role_has_permissionsModal" aria-labelledby="role_has_permissionsModalLabel" aria-hidden="true" tabindex="-1"> 
     <div class="modal-dialog modal-lg"> 
     <div class="modal-content"> 
         <div class="modal-header"> 
         <h1 class="modal-title fs-5" id="role_has_permissionsModalLabel">Role_has_permissions Modal</h1> 
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
                    <th>Permission_id</th>
                    <th>Role_id</th>

                                </tr> 
                            </thead> 
                            <tbody> 
                                @if ($role_has_permissionss->count() > 0) 
                                    @foreach ($role_has_permissionss as $datas) 
                                        <tr class="px-4"> 
                                            <td style="text-align: center;"> 
                                                  <button class="btn btn-sm btn-danger text-white" type="button" wire:click="role_has_permissionsLOV({{ $datas->idrole_has_permissions }}, 'role_has_permissions')"><i class="fa-solid fa-check"></i></button> 
                                            </td> 
                                            <td>{{ $datas->permission_id }}</td> 
                                            <td>{{ $datas->role_id }}</td> 
                                        </tr> 
                                    @endforeach 
                                @else 
                                    <tr> 
                                        <td colspan=" 1 " style="text-align: center;"><small>Data Tidak di temukan</small></td> 
                                    </tr> 
                                @endif 
                            </tbody> 
                        </table>  
                        </div>  
      <div class=" d-flex justify-content-between align-items-center"> 
          <div> 
           Showing {{ $role_has_permissionss->firstItem() }} to {{ $role_has_permissionss->lastItem() }} of {{ $role_has_permissionss->total() }} results
          </div> 
           <div class="mt-2"> 
               {{ $role_has_permissionss->links() }} 
           </div>
          </div> 
          </div> 
      </div>
      </div>
  </div> 
