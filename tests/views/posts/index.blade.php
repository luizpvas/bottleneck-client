<div>
  @foreach($posts as $post)
  <h1>{{ $post->title }}</h1>
  <div>By {{ $post->author->name }}</div>
  @endforeach
</div>