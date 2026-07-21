<x-layouts.app title="Articles Management">
    <div class="container py-5">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Articles</li>
            </ol>
        </nav>
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h1 class="display-5 fw-bold text-primary mb-3"><i class="bi bi-journal-text me-2"></i>Articles Management</h1>
                <p class="lead text-muted">Review, publish and manage articles</p>
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
                                        <th scope="col">Title</th>
                                        <th scope="col">Author</th>
                                        <th scope="col">Published</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($articles as $article)
                                    <tr>
                                        <th scope="row">{{$article->id}}</th>
                                        <td>{{$article->title}}</td>
                                        <td>{{$article->user->name}}</td>
                                        <td>
                                            @if($article->published)
                                                <span class="badge bg-success">Yes</span>
                                            @else
                                                <span class="badge bg-secondary">No</span>
                                            @endif
                                        </td>
                                        <td>
                                            <form action="{{route('admin.articles.toggle',$article->id)}}" method="post" class="d-inline">
                                                @csrf
                                                @if($article->published)
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle me-1"></i> Unpublish</button>
                                                @else
                                                <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-check-circle me-1"></i> Publish</button>
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
