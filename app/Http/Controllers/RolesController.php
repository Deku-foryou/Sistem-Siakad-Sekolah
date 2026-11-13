<?php

namespace App\Http\Controllers;

use App\Models\Permissions;
use App\Models\Role_has_permissions;
use App\Models\Roles;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class RolesController extends Controller
{
    public function permissionsoption($id = null)
    {
        $result = Permissions::get();
        $options = '';

        foreach ($result as $row) {
            $options .= '<option value="'.$row->id.'"';
            if ($id == $row->id) {
                $options .= ' selected';
            }
            $options .= '>'.$row->name.'</option>';
        }

        return $options;
    }

    public function index(Request $request)
    {

        $data = Roles::all();

        if ($request->ajax()) {
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('permission_names', function ($row) {

                    $permission_role = Role_has_permissions::with(['roles', 'permissions'])->where('role_id', $row->id)->get();

                    if ($permission_role->isEmpty()) {
                        return '<div class="text-danger italic" style="font-size:12px;">Belum ada permissions</div>';
                    }

                    $bayarBulan = $permission_role->map(function ($row) {
                        return '<span class="badge text-badge" style="font-size:12px;">'.$row->permissions->name.'</span> ';
                    })->unique()->sortByDesc(null)->implode(' ');

                    return $bayarBulan;

                })
                ->addColumn('action', function ($row) {
                    $btn = ' 
                    <div class="btn-grup"  style="white-space: nowrap !important;">
                    <button type="button" class="detilroles btn btn-success btn-sm text-white"  id="'.$row->id.'"><i class="fa-solid fa-user-shield"></i></button>
                    <button type="button" class="editroles btn btn-warning btn-sm"  id="'.$row->id.'" ><i class="fas fa-edit"></i></button>
                    <button type="button" class="deleteroles btn btn-danger btn-sm text-white"  id="'.$row->id.'" ><i class="fas fa-trash-alt"></i></button> 
                    </div>
                     
                    ';

                    return $btn;
                })
                ->rawColumns(['action', 'permission_names'])
                ->make(true);
        }

        return view('roles');
    }

    public function store(Request $request)
    {
        if ($request->operationroles == 'Add') {
            $roles = new Roles;
            $roles->id = $request->id;
            $roles->name = $request->name;
            $roles->guard_name = 'web';
            $roles->save();
            $lastid = $roles->id;
        } else {
            $roles = Roles::where('id', $request->id)
                ->update([
                    'id' => $request->id,
                    'name' => $request->name,
                    'guard_name' => 'web',
                ]);
        }

        return response()->json([
            'pesan' => 'sukses',
        ]);
    }

    public function edit()
    {
        $id = $_GET['id'];

        $data = Roles::findOrFail($id);
        $response = json_encode($data);

        return response()->json([
            'data' => $response,
            'pesan' => 'sukses',
        ]);
    }

    public function delete()
    {
        $id = $_GET['id'];
        $data = Roles::where('id', $id)
            ->delete();

        return response()->json([
            'pesan' => 'sukses',
        ]);
    }

    public function rolesoption($id = null)
    {
        $result = Roles::get();
        $options = '';

        foreach ($result as $row) {
            $options .= '<option value="'.$row->id.'"';
            if ($id == $row->id) {
                $options .= ' selected';
            }
            $options .= '>'.$row->roles.'</option>';
        }

        return $options;
    }
}
