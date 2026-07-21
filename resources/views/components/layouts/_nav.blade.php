<nav class="navbar navbar-expand-lg position-sticky top-0 w-100" style="background: #fff; border-bottom: 1.5px solid #e3e6ea; z-index: 1000; box-shadow: 0 2px 12px 0 rgba(0,0,0,0.03);">
  <div class="container-fluid py-2">
    <a class="navbar-brand fw-bold fs-3 text-primary d-flex align-items-center gap-2" href="{{route('home')}}" style="letter-spacing: 1px;">
      <span class="logo-circle d-flex align-items-center justify-content-center"><i class="bi bi-shield-lock-fill"></i></span>
      CyberBlog
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
        <!-- <li class="nav-item">
          <a class="nav-link fw-semibold fs-5 px-3 text-primary nav-underline position-relative" aria-current="page" href="{{route('home')}}">Home</a>
        </li> -->
        <li class="nav-item">
          <a class="nav-link fw-semibold fs-5 px-3 text-primary nav-underline position-relative" href="{{ route('about') }}">About</a>
        </li>
        <li class="nav-item">
          <a class="nav-link fw-semibold fs-5 px-3 text-primary nav-underline position-relative" href="{{ route('contact') }}">Contact</a>
        </li>
        <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
          <a class="btn start-writing-nav-btn d-flex align-items-center px-4 py-2 fw-bold shadow-sm" href="{{route('articles.create')}}">
            <i class="bi bi-pencil-square me-2"></i>Start Writing
          </a>
        </li>
      </ul>
      <form action="{{route('articles.search')}}" class="d-flex mx-lg-3 my-2 my-lg-0" role="search">
        <input class="form-control me-2 search-input" type="search" placeholder="Search" aria-label="Search" name="search">
        <button class="btn search-btn d-flex align-items-center justify-content-center" type="submit">
          <i class="bi bi-search"></i>
        </button>
      </form>
      <ul class="navbar-nav ms-lg-2">
        <li class="nav-item dropdown">
          @guest
          <a class="nav-link dropdown-toggle fw-semibold text-primary d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="avatar-circle"><i class="bi bi-person"></i></span> Account
          </a>
          <ul class="dropdown-menu dropdown-menu-end custom-dropdown">
            <li><a class="dropdown-item" href="/login">Login</a></li>
            <li><a class="dropdown-item" href="/register">Register</a></li>
          </ul>
          @endguest
          @auth
          <a class="nav-link dropdown-toggle fw-semibold text-primary d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="avatar-circle p-0" style="overflow:hidden; background:transparent;">
              @php
                $user = auth()->user();
                $avatarUrl = $user->avatar
                  ? Storage::url("public/images/users/{$user->id}/{$user->avatar}")
                  : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=2ECC71&color=fff';
              @endphp
              <img src="{{$avatarUrl}}" alt="avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">
            </span>
            <span class="d-none d-lg-inline">Hi, {{$user->name}}</span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end custom-dropdown">
            <li><a class="dropdown-item" href="{{route('profile')}}"><i class="bi bi-person me-2"></i>Profile</a></li>
            @if(auth()->user()->isAdmin())
            <li><a class="dropdown-item" href="{{route('dashboard')}}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
            @endif
            <li><hr class="dropdown-divider"></li>
            <form action="/logout" method="POST">
              @csrf
              <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
            </form>
          </ul>
          @endauth
        </li>
      </ul>
    </div>
  </div>
</nav>
