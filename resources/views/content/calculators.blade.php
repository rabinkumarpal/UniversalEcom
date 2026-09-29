@extends('layouts.storefront')

@section('title', 'Material Estimators & Construction Quantity Calculators — Universal Commerce')
@section('meta_description', 'Estimate required quantities of cement bags, sand, aggregates, red bricks, and wall paints for your residential or commercial site project.')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumbs -->
    <nav class="flex text-xs text-slate-500 mb-6 gap-2">
        <a href="{{ route('storefront.home') }}" class="hover:text-indigo-600">Home</a>
        <span>/</span>
        <span class="text-slate-900 font-semibold">Material Calculators</span>
    </nav>

    <div class="text-center max-w-2xl mx-auto mb-12">
        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 block mb-1">Engineering Tools</span>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Material Quantity Estimators</h1>
        <p class="text-xs text-slate-500 mt-2">
            Calculate accurate quantities of structural materials according to Indian IS civil engineering standards to plan your site procurement without excess wastage.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- 1. Concrete Slab Calculator -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between" x-data="{
            length: 30,
            width: 20,
            thickness: 5,
            grade: 'M20',
            get results() {
                let volumeCuFt = this.length * this.width * (this.thickness / 12);
                let volumeCuM = volumeCuFt / 35.315;
                let dryVolume = volumeCuM * 1.54;
                let parts = this.grade === 'M25' ? 4 : 5.5; // M20 (1:1.5:3=5.5), M25 (1:1:2=4)
                let cementRatio = 1 / parts;
                let sandRatio = (this.grade === 'M25' ? 1 : 1.5) / parts;
                let aggRatio = (this.grade === 'M25' ? 2 : 3) / parts;

                let cementKg = dryVolume * cementRatio * 1440;
                let cementBags = Math.ceil(cementKg / 50);
                let sandTonnes = (dryVolume * sandRatio * 1.6).toFixed(2);
                let aggTonnes = (dryVolume * aggRatio * 1.5).toFixed(2);

                return {
                    cuM: volumeCuM.toFixed(1),
                    cementBags: cementBags,
                    sandTonnes: sandTonnes,
                    aggTonnes: aggTonnes
                };
            }
        }">
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-sm">
                        1
                    </div>
                    <h3 class="font-bold text-slate-900 text-base">RCC Slab Concrete</h3>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Length (Feet)</label>
                            <input type="number" min="1" x-model.number="length" class="w-full p-2 border border-slate-300 rounded-lg font-mono">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Width (Feet)</label>
                            <input type="number" min="1" x-model.number="width" class="w-full p-2 border border-slate-300 rounded-lg font-mono">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Thickness (Inches)</label>
                            <input type="number" min="3" max="18" x-model.number="thickness" class="w-full p-2 border border-slate-300 rounded-lg font-mono">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Concrete Grade</label>
                            <select x-model="grade" class="w-full p-2 border border-slate-300 rounded-lg font-bold text-xs">
                                <option value="M20">M20 (1 : 1.5 : 3)</option>
                                <option value="M25">M25 (1 : 1 : 2)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                        <div class="flex justify-between font-medium">
                            <span class="text-slate-500">Total Volume:</span>
                            <strong class="font-mono text-slate-800" x-text="results.cuM + ' m³'"></strong>
                        </div>
                        <div class="flex justify-between font-bold text-slate-900">
                            <span>Cement Bags (50kg):</span>
                            <span class="font-mono text-indigo-600 font-black text-sm" x-text="results.cementBags + ' Bags'"></span>
                        </div>
                        <div class="flex justify-between text-slate-700">
                            <span>Manufactured Sand:</span>
                            <span class="font-mono" x-text="results.sandTonnes + ' Tonnes'"></span>
                        </div>
                        <div class="flex justify-between text-slate-700">
                            <span>20mm Coarse Aggregate:</span>
                            <span class="font-mono" x-text="results.aggTonnes + ' Tonnes'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-5">
                <a href="{{ route('storefront.catalog') }}" class="w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow transition">
                    Shop Cement & Aggregates &rarr;
                </a>
            </div>
        </div>

        <!-- 2. Brickwork Masonry Calculator -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between" x-data="{
            wallLength: 20,
            wallHeight: 10,
            thicknessType: '9',
            get results() {
                let areaSqFt = this.wallLength * this.wallHeight;
                let brickMultiplier = this.thicknessType === '9' ? 9.5 : 4.8;
                let rawBricks = Math.ceil(areaSqFt * brickMultiplier);
                let totalBricks = Math.ceil(rawBricks * 1.05); // 5% site breakage allowance
                let cementBags = Math.ceil(totalBricks * (this.thicknessType === '9' ? 0.018 : 0.009));
                let sandTonnes = (totalBricks * (this.thicknessType === '9' ? 0.0035 : 0.0018)).toFixed(2);

                return {
                    area: areaSqFt,
                    bricks: totalBricks,
                    cement: cementBags,
                    sand: sandTonnes
                };
            }
        }">
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-black text-sm">
                        2
                    </div>
                    <h3 class="font-bold text-slate-900 text-base">Brickwork Masonry</h3>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Wall Length (Feet)</label>
                            <input type="number" min="1" x-model.number="wallLength" class="w-full p-2 border border-slate-300 rounded-lg font-mono">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Wall Height (Feet)</label>
                            <input type="number" min="1" x-model.number="wallHeight" class="w-full p-2 border border-slate-300 rounded-lg font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Wall Thickness</label>
                        <select x-model="thicknessType" class="w-full p-2 border border-slate-300 rounded-lg font-bold text-xs">
                            <option value="9">9 Inch Main External Wall (Double Leaf)</option>
                            <option value="4.5">4.5 Inch Internal Partition Wall (Single Leaf)</option>
                        </select>
                    </div>

                    <div class="mt-4 p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                        <div class="flex justify-between font-medium">
                            <span class="text-slate-500">Wall Face Area:</span>
                            <strong class="font-mono text-slate-800" x-text="results.area + ' sq. ft.'"></strong>
                        </div>
                        <div class="flex justify-between font-bold text-slate-900">
                            <span>Total Red Bricks:</span>
                            <span class="font-mono text-amber-600 font-black text-sm" x-text="results.bricks.toLocaleString() + ' Nos.'"></span>
                        </div>
                        <div class="flex justify-between text-slate-700">
                            <span>Mortar Cement (50kg):</span>
                            <span class="font-mono" x-text="results.cement + ' Bags'"></span>
                        </div>
                        <div class="flex justify-between text-slate-700">
                            <span>Plastering Sand:</span>
                            <span class="font-mono" x-text="results.sand + ' Tonnes'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-5">
                <a href="{{ route('storefront.catalog') }}" class="w-full py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs flex items-center justify-center gap-1.5 shadow transition">
                    Shop Bricks & Mortar &rarr;
                </a>
            </div>
        </div>

        <!-- 3. Wall Painting Calculator -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between" x-data="{
            carpetArea: 1000,
            coats: 2,
            surfaceType: 'interior',
            get results() {
                // Interior total wall surface is approx 3.5x carpet area (walls + ceiling)
                let wallArea = this.carpetArea * 3.5;
                let primerLiters = Math.ceil(wallArea / 140);
                let paintCoverage = this.surfaceType === 'interior' ? 120 : 90; // sq ft per liter per coat
                let paintLiters = Math.ceil((wallArea * this.coats) / paintCoverage);

                return {
                    wallSurface: Math.round(wallArea),
                    primer: primerLiters,
                    paint: paintLiters
                };
            }
        }">
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-black text-sm">
                        3
                    </div>
                    <h3 class="font-bold text-slate-900 text-base">Wall Paint Coverage</h3>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Carpet / Built-up Area (Sq. Ft.)</label>
                        <input type="number" min="50" step="50" x-model.number="carpetArea" class="w-full p-2 border border-slate-300 rounded-lg font-mono">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Number of Coats</label>
                            <select x-model.number="coats" class="w-full p-2 border border-slate-300 rounded-lg font-bold text-xs">
                                <option value="2">2 Coats (Standard)</option>
                                <option value="3">3 Coats (Deep Color)</option>
                                <option value="1">1 Coat (Touch-up)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Surface Type</label>
                            <select x-model="surfaceType" class="w-full p-2 border border-slate-300 rounded-lg font-bold text-xs">
                                <option value="interior">Interior Smooth</option>
                                <option value="exterior">Exterior Textured</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                        <div class="flex justify-between font-medium">
                            <span class="text-slate-500">Total Wall Surface:</span>
                            <strong class="font-mono text-slate-800" x-text="results.wallSurface.toLocaleString() + ' sq. ft.'"></strong>
                        </div>
                        <div class="flex justify-between font-bold text-slate-900">
                            <span>Acrylic Wall Primer:</span>
                            <span class="font-mono text-emerald-600 font-black text-sm" x-text="results.primer + ' Litres'"></span>
                        </div>
                        <div class="flex justify-between font-bold text-slate-900">
                            <span>Emulsion Top Coat:</span>
                            <span class="font-mono text-indigo-600 font-black text-sm" x-text="results.paint + ' Litres'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-5">
                <a href="{{ route('storefront.catalog') }}" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow transition">
                    Shop Paints & Primer &rarr;
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
