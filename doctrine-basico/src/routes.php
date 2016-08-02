<?php

use Zend\Diactoros\ServerRequestFactory;
use Aura\Router\RouterContainer;
use Zend\Diactoros\Response;
use Slim\Views\PhpRenderer;
use App\Entity\Category;
use App\Entity\Post;
use Psr\Http\Message\ServerRequestInterface;

$request = ServerRequestFactory::fromGlobals(
  $_SERVER,$_GET,$_POST,$_COOKIE,$_FILES
);

$routerContainer = new RouterContainer();

$generator = $routerContainer->getGenerator();
$map = $routerContainer->getMap();

$view = new PhpRenderer('../templates/');

$entityManager = getEntityManager();

$map->get('home','/',function(ServerRequestInterface $request,$response) use($view, $entityManager){
    $postRepository = $entityManager->getRepository(Post::class);
    $categoryRepository = $entityManager->getRepository(Category::class);
    $categories = $categoryRepository->findAll();

    $data = $request->getQueryParams();
    if(isset($data['search']) and $data['search']!=''){
        $queryBuilder = $postRepository->createQueryBuilder('p');
        $queryBuilder->join('p.categories','c')->where($queryBuilder->expr()->eq('c.id',$data['search']));
        $posts = $queryBuilder->getQuery()->getResult();
    }else{
        $posts = $postRepository->findAll();
    }

    return $view->render($response,'home.phtml',[
        'posts' => $posts,
        'categories' => $categories
    ]);
});

include_once 'categories.php';
include_once 'posts.php';

$matcher = $routerContainer->getMatcher();

$route = $matcher->match($request);

foreach ($route->attributes as $key => $value) {
    $request = $request->withAttribute($key,$value);
}

$callable = $route->handler;

$response = $callable($request, new Response());

if($response instanceof Response\RedirectResponse){
    header("location:{$response->getHeader("location")[0]}");
}else if($response instanceof Response){
    echo $response->getBody();
}


