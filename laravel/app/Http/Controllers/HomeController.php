<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if (! $request->user()->isCustomerService()) {
            return redirect()->route('dashboard');
        }

        $category = Category::active()->orderBy('id')->first();

        abort_if($category === null, 404, 'Belum ada kategori layanan yang aktif.');

        return redirect()->route('leads.index', ['category' => $category]);
    }
}
