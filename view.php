<?php

// =============================================================================
// LARAVEL PHARMACY INVENTORY SYSTEM — UNIFIED EXAM CHEAT SHEET
// =============================================================================




// =============================================================================
// [1] MIGRATIONS
// =============================================================================

// --- medicines migration ---
Schema::create('medicines', function (Blueprint $table) {
    $table->id();
    $table->string('medicine_name');
    $table->string('category'); // Antibiotic, Painkiller, Vitamin, Antiviral
    $table->decimal('price_per_unit', 8, 2);
    $table->integer('stock_quantity');
    $table->date('expiration_date');
    $table->timestamps();
});

// --- stock_ins migration ---
Schema::create('stock_ins', function (Blueprint $table) {
    $table->id();
    $table->foreignId('medicine_id')->constrained()->onDelete('cascade');
    $table->integer('quantity');
    $table->date('date_received');
    $table->decimal('total_value', 8, 2);
    $table->timestamps();
});




// =============================================================================
// [2] MODELS
// =============================================================================

// --- app/Models/Medicine.php ---
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    protected $fillable = [
        'medicine_name',
        'category',
        'price_per_unit',
        'stock_quantity',
        'expiration_date',
    ];

    public function stockIns()
    {
        return $this->hasMany(StockIn::class);
    }
}


// --- app/Models/StockIn.php ---
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockIn extends Model
{
    protected $fillable = [
        'medicine_id',
        'quantity',
        'date_received',
        'total_value',
    ];

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }
}




// =============================================================================
// [3] ROUTES — web.php
// =============================================================================

use App\Http\Controllers\MedicineController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\ReportController;

Auth::routes();

Route::middleware('auth')->group(function () {
    Route::get('/', fn() => redirect()->route('medicines.index'));

    // Medicine CRUD
    Route::resource('medicines', MedicineController::class);

    // Stock-In CRUD
    Route::resource('stock_ins', StockInController::class);

    // Reports
    Route::get('/reports',        [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
});




// =============================================================================
// [4] MEDICINE CONTROLLER — app/Http/Controllers/MedicineController.php
// =============================================================================

namespace App\Http\Controllers;

use App\Models\Medicine;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    public function index()
    {
        $medicines = Medicine::latest()->get();
        return view('medicines.index', compact('medicines'));
    }

    public function create()
    {
        return view('medicines.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'medicine_name'  => 'required|string|max:255',
            'category'       => 'required|string',
            'price_per_unit' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'expiration_date'=> 'required|date',
        ]);

        Medicine::create($request->all());

        return redirect()->route('medicines.index')
                         ->with('success', 'Medicine Added Successfully');
    }

    public function edit(Medicine $medicine)
    {
        return view('medicines.edit', compact('medicine'));
    }

    public function update(Request $request, Medicine $medicine)
    {
        $request->validate([
            'medicine_name'  => 'required|string|max:255',
            'category'       => 'required|string',
            'price_per_unit' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'expiration_date'=> 'required|date',
        ]);

        $medicine->update($request->all());

        return redirect()->route('medicines.index')
                         ->with('success', 'Medicine Updated Successfully');
    }

    public function destroy(Medicine $medicine)
    {
        $medicine->delete();

        return redirect()->route('medicines.index')
                         ->with('success', 'Medicine Deleted Successfully');
    }
}




// =============================================================================
// [5] STOCK-IN CONTROLLER — app/Http/Controllers/StockInController.php
// =============================================================================

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\StockIn;
use Illuminate\Http\Request;

class StockInController extends Controller
{
    public function index()
    {
        $stockins = StockIn::with('medicine')->latest()->get();
        return view('stock_ins.index', compact('stockins'));
    }

    public function create()
    {
        $medicines = Medicine::all();
        return view('stock_ins.create', compact('medicines'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'medicine_id'   => 'required|exists:medicines,id',
            'quantity'      => 'required|integer|min:1',
            'date_received' => 'required|date',
        ]);

        $medicine    = Medicine::find($request->medicine_id);
        $total_value = $request->quantity * $medicine->price_per_unit;

        StockIn::create([
            'medicine_id'   => $request->medicine_id,
            'quantity'      => $request->quantity,
            'date_received' => $request->date_received,
            'total_value'   => $total_value,
        ]);

        return redirect()->route('stock_ins.index')
                         ->with('success', 'Stock Added Successfully');
    }
}




// =============================================================================
// [6] REPORT CONTROLLER (PDF EXPORT) — app/Http/Controllers/ReportController.php
// =============================================================================
// Run first: composer require barryvdh/laravel-dompdf
// Then:      php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"

namespace App\Http\Controllers;

use App\Models\StockIn;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    // Show report page
    public function index()
    {
        $stockins = StockIn::with('medicine')->latest()->get();
        return view('reports.index', compact('stockins'));
    }

    // Export as PDF
    public function export()
    {
        $stockins = StockIn::with('medicine')->latest()->get();

        $pdf = Pdf::loadView('reports.pdf', compact('stockins'));

        return $pdf->download('stock_report_' . date('Y-m-d') . '.pdf');
    }
}




// =============================================================================
// [7] BLADE VIEWS
// =============================================================================
?>




{{-- =========================================================================
     resources/views/layouts/app.blade.php
     ========================================================================= --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pharmacy Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark px-3">
    <a class="navbar-brand" href="#">Pharmacy</a>
    <div>
        <a class="btn btn-outline-light btn-sm me-2" href="{{ route('medicines.index') }}">Medicines</a>
        <a class="btn btn-outline-light btn-sm me-2" href="{{ route('stock_ins.index') }}">Stock-In</a>
        <a class="btn btn-outline-light btn-sm me-2" href="{{ route('reports.index') }}">Reports</a>
        <form action="{{ route('logout') }}" method="POST" class="d-inline">
            @csrf
            <button class="btn btn-outline-danger btn-sm">Logout</button>
        </form>
    </div>
</nav>
<div class="container mt-4">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @yield('content')
</div>
</body>
</html>




{{-- =========================================================================
     resources/views/medicines/index.blade.php
     ========================================================================= --}}

@extends('layouts.app')
@section('content')
<div class="card">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
        <span>Medicines</span>
        <a href="{{ route('medicines.create') }}" class="btn btn-primary btn-sm">+ Add Medicine</a>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Expiration</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($medicines as $medicine)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $medicine->medicine_name }}</td>
                    <td>{{ $medicine->category }}</td>
                    <td>₱{{ number_format($medicine->price_per_unit, 2) }}</td>
                    <td>{{ $medicine->stock_quantity }}</td>
                    <td>{{ $medicine->expiration_date }}</td>
                    <td>
                        <a href="{{ route('medicines.edit', $medicine) }}" class="btn btn-warning btn-sm">Edit</a>
                        <form action="{{ route('medicines.destroy', $medicine) }}" method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this medicine?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection




{{-- =========================================================================
     resources/views/medicines/create.blade.php
     ========================================================================= --}}

@extends('layouts.app')
@section('content')
<div class="card col-md-6">
    <div class="card-header fw-bold">Add Medicine</div>
    <div class="card-body">
        <form action="{{ route('medicines.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label>Medicine Name</label>
                <input type="text" name="medicine_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Category</label>
                <select name="category" class="form-control" required>
                    <option>Antibiotic</option>
                    <option>Painkiller</option>
                    <option>Vitamin</option>
                    <option>Antiviral</option>
                </select>
            </div>
            <div class="mb-3">
                <label>Price per Unit</label>
                <input type="number" step="0.01" name="price_per_unit" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Stock Quantity</label>
                <input type="number" name="stock_quantity" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Expiration Date</label>
                <input type="date" name="expiration_date" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('medicines.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection




{{-- =========================================================================
     resources/views/medicines/edit.blade.php
     ========================================================================= --}}

@extends('layouts.app')
@section('content')
<div class="card col-md-6">
    <div class="card-header fw-bold">Edit Medicine</div>
    <div class="card-body">
        <form action="{{ route('medicines.update', $medicine) }}" method="POST">
            @csrf @method('PUT')
            <div class="mb-3">
                <label>Medicine Name</label>
                <input type="text" name="medicine_name" class="form-control"
                       value="{{ $medicine->medicine_name }}" required>
            </div>
            <div class="mb-3">
                <label>Category</label>
                <select name="category" class="form-control" required>
                    @foreach(['Antibiotic','Painkiller','Vitamin','Antiviral'] as $cat)
                        <option {{ $medicine->category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label>Price per Unit</label>
                <input type="number" step="0.01" name="price_per_unit" class="form-control"
                       value="{{ $medicine->price_per_unit }}" required>
            </div>
            <div class="mb-3">
                <label>Stock Quantity</label>
                <input type="number" name="stock_quantity" class="form-control"
                       value="{{ $medicine->stock_quantity }}" required>
            </div>
            <div class="mb-3">
                <label>Expiration Date</label>
                <input type="date" name="expiration_date" class="form-control"
                       value="{{ $medicine->expiration_date }}" required>
            </div>
            <button type="submit" class="btn btn-warning">Update</button>
            <a href="{{ route('medicines.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection




{{-- =========================================================================
     resources/views/stock_ins/index.blade.php
     ========================================================================= --}}

@extends('layouts.app')
@section('content')
<div class="card">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
        <span>Stock-In Records</span>
        <a href="{{ route('stock_ins.create') }}" class="btn btn-primary btn-sm">+ Add Stock</a>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Medicine Name</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Price/Unit</th>
                    <th>Total Value</th>
                    <th>Date Received</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stockins as $stockin)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $stockin->medicine->medicine_name }}</td>
                    <td>{{ $stockin->medicine->category }}</td>
                    <td>{{ $stockin->quantity }}</td>
                    <td>₱{{ number_format($stockin->medicine->price_per_unit, 2) }}</td>
                    <td>₱{{ number_format($stockin->total_value, 2) }}</td>
                    <td>{{ $stockin->date_received }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection




{{-- =========================================================================
     resources/views/stock_ins/create.blade.php
     ========================================================================= --}}

@extends('layouts.app')
@section('content')
<div class="card col-md-6">
    <div class="card-header fw-bold">Add Stock-In</div>
    <div class="card-body">
        <form action="{{ route('stock_ins.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label>Medicine</label>
                <select name="medicine_id" class="form-control" required>
                    <option value="">-- Select Medicine --</option>
                    @foreach($medicines as $medicine)
                        <option value="{{ $medicine->id }}">{{ $medicine->medicine_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label>Quantity Received</label>
                <input type="number" name="quantity" class="form-control" min="1" required>
            </div>
            <div class="mb-3">
                <label>Date Received</label>
                <input type="date" name="date_received" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('stock_ins.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection




{{-- =========================================================================
     resources/views/reports/index.blade.php
     ========================================================================= --}}

@extends('layouts.app')
@section('content')
<div class="card">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
        <span>Stock-In Report</span>
        <a href="{{ route('reports.export') }}" class="btn btn-danger btn-sm">Export PDF</a>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Medicine Name</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Total Value</th>
                    <th>Date Received</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stockins as $stockin)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $stockin->medicine->medicine_name }}</td>
                    <td>{{ $stockin->medicine->category }}</td>
                    <td>{{ $stockin->quantity }}</td>
                    <td>₱{{ number_format($stockin->total_value, 2) }}</td>
                    <td>{{ $stockin->date_received }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection




{{-- =========================================================================
     resources/views/reports/pdf.blade.php   ← used by DomPDF
     ========================================================================= --}}

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body  { font-family: Arial, sans-serif; font-size: 12px; }
        h2    { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th    { background-color: #1e3a5f; color: #fff; padding: 6px; text-align: left; }
        td    { border: 1px solid #ccc; padding: 6px; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .date { text-align: right; font-size: 11px; color: #555; }
    </style>
</head>
<body>
    <h2>Pharmacy Stock-In Report</h2>
    <p class="date">Generated: {{ date('F d, Y') }}</p>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Medicine Name</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Total Value</th>
                <th>Date Received</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stockins as $stockin)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $stockin->medicine->medicine_name }}</td>
                <td>{{ $stockin->medicine->category }}</td>
                <td>{{ $stockin->quantity }}</td>
                <td>₱{{ number_format($stockin->total_value, 2) }}</td>
                <td>{{ $stockin->date_received }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>




{{-- =========================================================================
     resources/views/auth/login.blade.php
     ========================================================================= --}}

@extends('layouts.app')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header fw-bold">Login</div>
            <div class="card-body">
                <form action="{{ route('login') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection




<?php
// =============================================================================
// QUICK REFERENCE NOTES
// =============================================================================

// --- Install DomPDF ---
// composer require barryvdh/laravel-dompdf
// php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"

// --- Setup Auth (Laravel Breeze or UI) ---
// composer require laravel/ui
// php artisan ui bootstrap --auth
// npm install && npm run build

// --- Run Migrations ---
// php artisan migrate

// --- PDF Export Pattern (memorize this) ---
// 1. Import:   use Barryvdh\DomPDF\Facade\Pdf;
// 2. Load:     $pdf = Pdf::loadView('view.name', compact('data'));
// 3. Download: return $pdf->download('filename.pdf');

// --- Relationship Map ---
// Medicine  → standalone CRUD
// StockIn   → belongsTo Medicine  (medicine_id FK)
// Report    → reads StockIns + Medicine → exports PDF

// --- Total Value Formula ---
// $total_value = $request->quantity * $medicine->price_per_unit;
