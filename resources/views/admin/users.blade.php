<x-layouts.app title="Users Management">
  <div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Users</li>
      </ol>
    </nav>
    <div class="row mb-4">
      <div class="col-12 text-center">
        <h1 class="display-5 fw-bold text-primary mb-3"><i class="bi bi-people me-2"></i>Users Management</h1>
        <p class="lead text-muted">Manage users and their roles</p>
      </div>
    </div>
    <div class="row justify-content-center">
      <div class="col-12 col-lg-10">
        <div class="card shadow-lg border-0 p-4">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover table-bordered align-middle rounded">
                <thead class="table-light">
                  <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Is Admin</th>
                    <th scope="col">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($users as $user)
                  <tr>
                    <th scope="row">{{$user->id}}</th>
                    <td>{{$user->name}}</td>
                    <td>{{$user->email}}</td>
                    <td>
                      @if($user->isAdmin())
                        <span class="badge bg-success">Yes</span>
                      @else
                        <span class="badge bg-secondary">No</span>
                      @endif
                    </td>
                    <td>
                      <form action="{{route('admin.users.toggle',$user->id)}}" method="post" class="d-inline">
                          @csrf
                          @if($user->isAdmin())
                          <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-person-dash me-1"></i> Revoke admin</button>
                          @else
                          <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-person-check me-1"></i> Set admin</button>
                          @endif
                      </form>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-layouts.app>
