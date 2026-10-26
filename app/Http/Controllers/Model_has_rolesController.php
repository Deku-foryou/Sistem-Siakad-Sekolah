<?php 
namespace App\Http\Controllers; 
use Carbon\Carbon;
use App\Models\User;
use App\Models\Roles;
use Illuminate\Http\Request; 
use App\Models\Model_has_roles; 
use \Yajra\Datatables\Datatables;
use Spatie\Permission\Models\Role;
 
class Model_has_rolesController extends Controller 
{ 

    public function index(Request $request, $id) 
    { 

        $roles = Roles::get();

        $modelRoles = Model_has_roles::where('model_id', $id)
        ->pluck('role_id', 'role_id')
        ->all();

        $data = Model_has_roles::with(['roles', 'model'])->where('model_id', $id)->get(); 

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
                    <!-- <button type="button" class="detilmodel_has_roles btn btn-success btn-sm"  id="' . $row->idmodel_has_roles . '"><i class="fas fa-th-list"></i></button> --> 
                     <!--<button type="button" class="editmodel_has_roles btn btn-warning btn-sm"  id="' . $row->idmodel_has_roles . '" ><i class="fas fa-edit"></i></button>--> 
                     <!--<button type="button" class="deletemodel_has_roles btn btn-danger btn-sm"  id="' . $row->idmodel_has_roles . '" ><i class="fas fa-trash-alt"></i></button> --> 
                    </div>
                     
                    '; 
                    return $btn; 
                }) 
                ->rawColumns(['action']) 
                ->make(true); 
        } 
        return view('model_has_roles', compact(['id','roles', 'modelRoles'])); 
    } 
 

     public function store(Request $request) 
     { 
         if ($request->operationmodel_has_roles == 'Add') { 


            $user = User::findOrFail($request->model_id);  
            $role = Role::findOrFail($request->role_id); 
    
            if ($role && $user) {
                $user->syncRoles($role);
            } else {
                return response()->json([
                    'pesan' => 'Role or user not found',
                ], 404);
            }

         } 
 
        return response()->json([
            'pesan' => 'sukses' 
        ]);
   } 
 
 
 public function edit() 
    { 
        $id = $_GET['id']; 
        $data = Model_has_roles::findOrFail($id); 
        $response = json_encode($data); 
        return response()->json([ 
            'data' => $response, 
            'pesan' => 'sukses' 
        ]); 
    } 
 
 
  public function delete() 
     { 
         $id = $_GET['id']; 
         $data = Model_has_roles::where('idmodel_has_roles', $id) 
             ->delete(); 
        return response()->json([
            'pesan' => 'sukses' 
        ]); 
     } 
 
 
   public function model_has_rolesoption($id = null) 
     { 
         $result = Model_has_roles::get(); 
         $options = ''; 
  
         foreach ($result as $row) { 
             $options .= '<option value="' . $row->idmodel_has_roles . '"'; 
             if ($id == $row->idmodel_has_roles) { 
                 $options .= ' selected'; 
             } 
             $options .= '>' . $row->model_has_roles . '</option>'; 
         } 
  
         return $options; 
     } 
  
  
   public function model_has_roleslov(Request $request) 
    { 
        if ($request->ajax()) { 
            $data = Model_has_roles::select('*'); 
            return Datatables::of($data) 
                ->addIndexColumn() 
                ->addColumn('action', function ($row) { 
                    $btn = '  
                    <button type="button" class="pilihmodel_has_roles btn btn-success btn-sm"  idmodel_has_roles="'.$row->idmodel_has_roles . '" model_has_roles="' . $row->model_has_roles . '" ><i class="fas fa-check"></i></button>  
                    </div> 
                      
                    '; 
                    return $btn; 
                }) 
                ->rawColumns(['action']) 
                ->make(true); 
        } 
        return view('model_has_roleslov'); 
    } 
  
  
} 
