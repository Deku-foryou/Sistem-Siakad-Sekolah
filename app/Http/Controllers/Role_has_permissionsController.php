<?php

namespace App\Http\Controllers;

use App\Models\Permissions;
use App\Models\Role_has_permissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\Datatables\Datatables;

class Role_has_permissionsController extends Controller
{
    public function index(Request $request, $id)
    {

        $permissions = Permissions::get();

        $rolePermissions = Role_has_permissions::where('role_id', $id)
            ->pluck('permission_id', 'permission_id')
            ->all();

        $data = Role_has_permissions::with(['roles', 'permissions'])->where('role_id', $id)->get();

        if ($request->ajax()) {

            return Datatables::of($data)
                ->addIndexColumn()
                ->editColumn('created_at_formatted', function ($row) {
                    return Carbon::parse($row->created_at)->format('Y-m-d');
                })
                ->editColumn('updated_at_formatted', function ($row) {
                    return Carbon::parse($row->updated_at)->format('Y-m-d');
                })
                ->addColumn('action', function ($row) {
                    $btn = ' 
                    <div class="btn-grup"  style="white-space: nowrap !important;"> 
                    <!-- <button type="button" class="detilrole_has_permissions btn btn-success btn-sm"  id="'.$row->idrole_has_permissions.'"><i class="fas fa-th-list"></i></button> --> 
                    <!--  <button type="button" class="editrole_has_permissions btn btn-warning btn-sm"  id="'.$row->idrole_has_permissions.'" ><i class="fas fa-edit"></i></button> --> 
                    <!-- <button type="button" class="deleterole_has_permissions btn btn-danger btn-sm"  id="'.$row->permission_id.'" ><i class="fas fa-trash-alt"></i></button> --> 
                    </div>
                     
                    ';

                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('role_has_permissions', compact(['id', 'permissions', 'rolePermissions']));
    }

    public function store(Request $request)
    {
        if ($request->operationrole_has_permissions == 'Add') {
            $permission = Permission::find($request->permission_id);
            $role = Role::find($request->role_id);

            if ($role && $permission) {
                $role->syncPermissions($permission);
            } else {
                return response()->json([
                    'pesan' => 'Pilih minimal satu Permission',
                ], 404);
            }
        }

        return response()->json([
            'pesan' => 'sukses',
        ]);
    }

    public function edit()
    {
        $id = $_GET['id'];
        $data = Role_has_permissions::findOrFail($id);
        $response = json_encode($data);

        return response()->json([
            'data' => $response,
            'pesan' => 'sukses',
        ]);
    }

    public function delete(Request $request) {}

    public function role_has_permissionsoption($id = null)
    {
        $result = Role_has_permissions::get();
        $options = '';

        foreach ($result as $row) {
            $options .= '<option value="'.$row->idrole_has_permissions.'"';
            if ($id == $row->idrole_has_permissions) {
                $options .= ' selected';
            }
            $options .= '>'.$row->role_has_permissions.'</option>';
        }

        return $options;
    }

    public function role_has_permissionslov(Request $request)
    {
        if ($request->ajax()) {
            $data = Role_has_permissions::select('*');

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '  
                    <button type="button" class="pilihrole_has_permissions btn btn-success btn-sm"  idrole_has_permissions="'.$row->idrole_has_permissions.'" role_has_permissions="'.$row->role_has_permissions.'" ><i class="fas fa-check"></i></button>  
                    </div> 
                      
                    ';

                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('role_has_permissionslov');
    }
}
