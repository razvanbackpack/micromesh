<?php

namespace App\Controllers;

use Core\Helpers\Request;
use Core\Http\Response;
use App\Repositories\UserRepository;

/**
 * Sample controller for testing dependency injection.
 */
class UserTestController extends Controller
{
    /**
     * Constructor injection - UserRepository will be resolved by container.
     */
    public function __construct(
        private UserRepository $users
    ) {
    }

    /**
     * List all users - tests constructor DI.
     */
    public function index(): string
    {
        $allUsers = $this->users->all();
        return Response::make($allUsers)
            ->json($allUsers)
            ->__toString();
    }

    /**
     * Show a single user - tests constructor DI + route parameters.
     */
    public function show(int $id): string
    {
        $user = $this->users->find($id);
        
        if (!$user) {
            return Response::make(['error' => 'User not found'])
                ->status(404)
                ->json(['error' => 'User not found'])
                ->__toString();
        }

        return Response::make($user)
            ->json($user)
            ->__toString();
    }

    /**
     * Store a user - tests method injection + Request.
     */
    public function store(Request $request): string
    {
        $data = $request->all();
        
        if (empty($data['name']) || empty($data['email'])) {
            return Response::make(['error' => 'Name and email required'])
                ->status(422)
                ->json(['error' => 'Name and email required'])
                ->__toString();
        }

        $user = $this->users->create($data);
        
        return Response::make($user)
            ->status(201)
            ->json($user)
            ->__toString();
    }
}
