<x-layouts.app title="Article">
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-10 col-lg-8">
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden mb-4 position-relative">
          <div class="position-relative">
            <img src="https://placehold.co/1000x150/lightblue/white?text=" class="card-img-top" alt="..." style="object-fit: cover; height: 320px; filter: brightness(0.7);">
            <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-end p-4" style="pointer-events:none;">
              <h1 class="display-4 fw-bold text-white mb-2" style="text-shadow: 2px 2px 8px #000;">{{ $article->title }}</h1>
              <div class="mb-3">
                <span class="badge bg-info text-dark fs-6"><i class="bi bi-person me-1"></i> {{$article->user->name}}</span>
              </div>
            </div>
          </div>
          <div class="card-body bg-white p-4">
            @if(auth()->id() === $article->user_id)
              <div class="d-flex mb-3 gap-2">
                <a href="{{route('articles.edit',$article)}}" class="btn btn-warning rounded-pill px-4 fw-semibold"><i class="bi bi-pencil me-1"></i> Edit</a>
                <form action="{{route('articles.destroy',$article)}}" method="POST">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold"><i class="bi bi-trash me-1"></i> Delete</button>
                </form>
              </div>
            @endif
            <div class="text-justify mb-4 fs-5 lh-lg">
              {!!$article->content !!}
            </div>
            <h5 class="mb-3 fw-bold"><i class="bi bi-chat-dots me-2"></i>Comments</h5>
            <form action="{{route('comments.store',$article->id)}}" method="post" class="mb-4">
              @csrf
              <div class="input-group">
                <input type="text" placeholder="Insert comment" class="form-control rounded-start-pill" name="content">
                <button type="submit" class="btn btn-success rounded-end-pill px-4">Send</button>
              </div>
            </form>
            <div class="pt-2">
              @forelse ($article->comments as $comment)
                <div class="mb-3 p-3 bg-light rounded-3 border">
                  <b class="text-primary"><i class="bi bi-person-circle me-1"></i>{{$comment->user->name}}{{ auth()->check() && $comment->user_id === auth()->id() ? ' (you)' : '' }}:</b>
                  <p class="mb-1">{{$comment->content}}</p>
                </div>
              @empty
                <b class="text-muted">No comments.. write the first one!</b>
              @endforelse
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-layouts.app>
