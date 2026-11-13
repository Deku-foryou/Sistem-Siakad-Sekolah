<?php

namespace App\Http\Controllers;

use App\Models\Model_has_roles;
use App\Models\Roles;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Yajra\Datatables\Datatables;

class UsersController extends Controller
{
    public function index(Request $request)
    {

        $roles = Roles::get();

        $data = User::latest();

        if ($request->ajax()) {
            return Datatables::of($data)
                ->addIndexColumn()
                ->editColumn('created_at_formatted', function ($row) {
                    return Carbon::parse($row->created_at)->format('Y-m-d');
                })
                ->editColumn('updated_at_formatted', function ($row) {
                    return Carbon::parse($row->updated_at)->format('Y-m-d');
                })
                ->addColumn('roles_name', function ($row) {

                    $roles = Model_has_roles::with(['roles', 'model'])->where('model_id', $row->id)->get();

                    if ($roles->isEmpty()) {
                        return '<div class="text-danger italic" style="font-size:12px;">Belum ada Role</div>';
                    }

                    $cetakRoles = $roles->map(function ($row) {
                        return '<span class="badge text-badge fw-medium" style="font-size:12px; color:#7e0a9b;">'.$row->roles->name.'</span> ';
                    })->unique()->sortByDesc(null)->implode(' ');

                    return $cetakRoles;

                })
                ->addColumn('action', function ($row) {
                    $btn = ' 
                    <div class="btn-grup"  style="white-space: nowrap !important;"> 
                  <button type="button" class="detilusers btn btn-success btn-sm"  id="'.$row->id.'"><i class="fa-solid fa-user-gear"></i></button> 
                   <!--  <button type="button" class="tambahroles btn btn-success btn-sm"  id="'.$row->id.'"><i class="fa-solid fa-circle-plus"></i></button> -->
                    <button type="button" class="editusers btn btn-warning btn-sm"  id="'.$row->id.'" ><i class="fas fa-edit"></i></button>
                    <button type="button" class="deleteusers btn btn-danger btn-sm"  id="'.$row->id.'" ><i class="fas fa-trash-alt"></i></button> 
                    </div>
                     
                    ';

                    return $btn;
                })
                ->rawColumns(['action', 'roles_name'])
                ->make(true);
        }

        return view('users', compact(['roles']));
    }

    public function store(Request $request)
    {
        if ($request->operationusers == 'Add') {
            $users = new User;
            $users->id = $request->id;
            $users->name = $request->name;
            $users->email = $request->email;
            $users->password = Hash::make($request->password);
            //  $users->remember_token= $request ->remember_token;
            $users->save();
            $lastid = $users->isDirty;
        } else {
            $users = User::where('id', $request->id)
                ->update([
                    'id' => $request->id,
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    //   'remember_token' => $request ->remember_token,
                ]);
        }

        return response()->json([
            'pesan' => 'sukses',
        ]);
    }

    public function edit()
    {
        $id = $_GET['id'];
        $data = User::findOrFail($id);
        $response = json_encode($data);

        return response()->json([
            'data' => $response,
            'pesan' => 'sukses',
        ]);
    }

    public function delete()
    {
        $id = $_GET['id'];
        $data = User::where('id', $id)
            ->delete();

        return response()->json([
            'pesan' => 'sukses',
        ]);
    }

    public function usersoption($id = null)
    {
        $result = User::get();
        $options = '';

        foreach ($result as $row) {
            $options .= '<option value="'.$row->id.'"';
            if ($id == $row->id) {
                $options .= ' selected';
            }
            $options .= '>'.$row->users.'</option>';
        }

        return $options;
    }
}
