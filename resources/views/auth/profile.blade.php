<x-layouts.app title="Profile">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <!-- Profile Header -->
        <div class="card shadow-lg mb-5 border-0 p-4 text-center">
          <div class="d-flex flex-column align-items-center">
            <div class="profile-avatar mb-3">
              <img src="{{$user->avatar ? Storage::url("public/images/users/$user->id/" . $user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=2ECC71&color=fff'}}"
                   class="avatar-img shadow border border-3 border-info"
                   alt="Avatar"
                   style="width: 140px; height: 140px; object-fit: cover; border-radius: 50%; display: block; margin: 0 auto;">
            </div>
            <h2 class="fw-bold mb-1">{{$user->name}}</h2>
            <div class="text-muted mb-3">{{$user->email}}</div>
              <form method="POST" action="{{route('change.img')}}" class="mb-3" enctype="multipart/form-data">
                  @csrf
                  <label for="">Change profile picture</label>
                  <div class="mb-3">
                      <input type="file" class="form-control" aria-label="file example" name="avatar" required>
                  </div>
                  <button type="submit" class="btn btn-primary">Save</button>
              </form>
              <!-- <form method="POST" action="{{route('change.img')}}" class="d-inline-block" enctype="multipart/form-data">
                @csrf
                <label class="form-label fw-semibold">Change profile picture</label>
                <div class="input-group mb-2 justify-content-center">
                  <input type="file" class="form-control w-auto" name="avatar" required>
                  <button type="submit" class="btn btn-outline-primary ms-2">Save</button>
                </div>
              </form> -->
          </div>
        </div>
        <!-- Articles Section -->
        <div class="card shadow-sm mb-5 border-0 p-4">
          <h3 class="fw-semibold mb-4"><i class="bi bi-journal-text me-2"></i>Your Articles</h3>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Title</th>
                  <th>Published</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($user->articles as $article)
                  <tr>
                    <td>{{$article->id}}</td>
                    <td><a href="{{route('articles.show',$article->id)}}" class="fw-semibold text-primary">{{$article->title}}</a></td>
                    <td>
                      @if($article->published)
                        <span class="badge bg-success">Yes</span>
                      @else
                        <span class="badge bg-secondary">No</span>
                      @endif
                    </td>
                    <td>
                      @if(auth()->id() === $article->user_id)
                        <a href="{{route('articles.edit',$article)}}" class="btn btn-sm btn-outline-warning me-2" title="Edit"><i class="bi bi-pencil"></i></a>
                        <form action="{{route('articles.destroy',$article)}}" method="POST" class="d-inline">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
        <!-- Settings Section -->
        <div class="card shadow-sm mb-5 border-0 p-4">
          <h3 class="fw-semibold mb-4"><i class="bi bi-gear me-2"></i>Settings</h3>
          <form method="POST" action="{{route('users.update')}}">
            @csrf
            @method('PATCH')
            <div class="mb-4">
              <label class="form-label fw-semibold">Name</label>
              <input class="form-control rounded-pill" type="text" name="name" value="{{old('name') ?? $user->name}}" placeholder="You really change your name?!">
            </div>
            <div class="mb-4">
              <label class="form-label fw-semibold">Email</label>
              <input class="form-control rounded-pill" type="email" name="email" placeholder="Your brand new email" value="{{old('email') ?? $user->email}}">
            </div>
            <button type="submit" class="btn btn-warning px-4 rounded-pill">Change</button>
          </form>
        </div>
        <!-- Documents Section -->
        <div class="card shadow-sm border-0 p-4 mb-4">
          <h3 class="fw-semibold mb-3"><i class="bi bi-file-earmark-text me-2"></i>Documents</h3>
          <form method="POST" action="{{route('files.upload')}}" enctype="multipart/form-data" class="mb-4">
            @csrf
            <label for="document" class="form-label fw-semibold">Upload a document</label>
            <div class="input-group">
              <input id="document" type="file" class="form-control" name="file" accept=".jpg,.jpeg,.png,.gif,.pdf" required>
              <button type="submit" class="btn btn-primary">Upload</button>
            </div>
          </form>
          @foreach ($files as $file)
            <a href="{{route('download.private', $file->uid)}}" class="d-block mb-2 text-decoration-none text-info fw-semibold">
              <i class="bi bi-file-earmark-arrow-down me-2"></i>{{$file->name}}
            </a>
          @endforeach
          @if ($files->isEmpty())
            <p class="text-muted">No private files uploaded.</p>
          @endif
          <hr>
          <a href="{{route('download','privacy')}}" class="d-block mb-2 text-decoration-none text-info fw-semibold"><i class="bi bi-file-earmark-pdf me-2"></i>Privacy policy</a>
          <a href="{{route('download','cookie-policy')}}" class="d-block text-decoration-none text-info fw-semibold"><i class="bi bi-file-earmark-pdf me-2"></i>Cookie policy</a>
        </div>
      </div>
    </div>
  </div>
  <!-- Bootstrap Icons CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</x-layouts.app>
