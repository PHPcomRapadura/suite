<?php

namespace App\Http\Controllers;

use App\Services\BlogService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(private readonly BlogService $blog) {}

    public function index(Request $request): View
    {
        $page = $request->query('page', '1');
        abort_unless(is_string($page) && ctype_digit($page) && (int) $page >= 1, 404);
        $page = (int) $page;

        $result = $this->blog->posts($page);
        $available = $result !== null;
        $posts = $result['posts'] ?? [];

        // Página além da última (ou vazia por qualquer motivo) só existe como /blog.
        abort_if($available && $page > 1 && $posts === [], 404);

        $paginator = new LengthAwarePaginator(
            $posts,
            $result['total'] ?? 0,
            $this->blog->perPage(),
            $page,
            ['path' => route('blog')]
        );

        $canonical = $page === 1 ? route('blog') : route('blog', ['page' => $page]);

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Blog',
            '@id' => route('blog').'#blog',
            'url' => route('blog'),
            'name' => 'Blog PHP com Rapadura',
            'description' => 'Artigos, novidades e registros dos eventos da comunidade PHP com Rapadura.',
            'inLanguage' => 'pt-BR',
            'publisher' => ['@id' => url('/').'#organization'],
            'blogPost' => array_map(fn (array $post) => [
                '@type' => 'BlogPosting',
                'headline' => $post['title'],
                'url' => $post['link'],
                'datePublished' => $post['date']->toDateString(),
                'author' => ['@type' => 'Person', 'name' => $post['author']['name']],
            ], $posts),
        ];

        return view('blog', [
            'posts' => $paginator,
            'available' => $available,
            'canonical' => $canonical,
            'blogUrl' => $this->blog->baseUrl(),
            'feedUrl' => $this->blog->feedUrl(),
            'jsonLd' => $jsonLd,
        ]);
    }
}
