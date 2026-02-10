<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#1f2937">
    <meta name="format-detection" content="telephone=no">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/icon-192.png">
    <title>Tri-E POS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        /* Cross-platform touch optimizations */
        * {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
        }
        
        /* Prevent pull-to-refresh on mobile */
        body {
            overscroll-behavior-y: contain;
        }
        
        /* Smooth scrolling for all platforms */
        .scroll-smooth {
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
        }
        
        /* Touch-friendly button states */
        .touch-btn:active {
            transform: scale(0.97);
            opacity: 0.9;
        }
        
        /* Safe area padding for notched devices */
        .safe-area-top {
            padding-top: env(safe-area-inset-top);
        }
        .safe-area-bottom {
            padding-bottom: env(safe-area-inset-bottom);
        }
        
        /* Prevent text selection on UI elements */
        .no-select {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        /* Hide scrollbar but allow scrolling */
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Mobile cart slide animation */
        .cart-slide-enter {
            transform: translateX(100%);
        }
        .cart-slide-enter-active {
            transform: translateX(0);
            transition: transform 0.3s ease-out;
        }
        .cart-slide-leave-active {
            transform: translateX(100%);
            transition: transform 0.3s ease-in;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 no-select safe-area-top" x-data="posSystem()">
    <div class="min-h-screen min-h-[100dvh]">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-30">
            <div class="px-3 sm:px-6 py-3 sm:py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 sm:gap-4">
                        <h1 class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">Tri-E POS</h1>
                        <p class="hidden sm:block text-sm text-gray-600 dark:text-gray-400">Process customer transactions</p>
                    </div>
                    <div class="flex items-center gap-2 sm:gap-4">
                        <span class="hidden sm:block text-sm text-gray-600 dark:text-gray-400" x-text="currentDateTime"></span>

                        <!-- Reprint Button -->
                        <button
                            @click="showReprintModal = true"
                            class="hidden sm:flex px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition touch-btn items-center gap-2"
                            title="Reprint Receipt"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span class="hidden lg:inline">Reprint</span>
                        </button>

                        <!-- Mobile Cart Toggle Button -->
                        <button
                            @click="showMobileCart = !showMobileCart"
                            class="lg:hidden relative px-3 py-2 bg-blue-600 text-white rounded-lg touch-btn"
                        >
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span
                                x-show="cart.length > 0"
                                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold"
                                x-text="cart.length"
                            ></span>
                        </button>

                        <a href="/" class="px-3 sm:px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition touch-btn text-sm sm:text-base">
                            <span class="hidden sm:inline">Back to Admin</span>
                            <svg class="w-5 h-5 sm:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="flex flex-col lg:flex-row h-[calc(100vh-65px)] h-[calc(100dvh-65px)] sm:h-[calc(100vh-89px)] sm:h-[calc(100dvh-89px)]">
            <!-- Products Section -->
            <div class="flex-1 overflow-hidden flex flex-col">
                <!-- Search and Filter Bar -->
                <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-3 sm:p-4">
                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
                        <div class="flex-1">
                            <input 
                                type="text" 
                                x-model="searchQuery"
                                @input="filterProducts()"
                                inputmode="search"
                                placeholder="Search products..."
                                class="w-full px-4 py-3 sm:py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-base"
                            >
                        </div>
                        <select 
                            x-model="selectedCategory"
                            @change="filterProducts()"
                            class="px-4 py-3 sm:py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-base"
                        >
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="flex-1 overflow-y-auto scroll-smooth hide-scrollbar p-3 sm:p-6 bg-gray-50 dark:bg-gray-900" :class="{ 'pb-24 lg:pb-6': cart.length > 0 }">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-2 sm:gap-4">
                        <template x-for="product in filteredProducts" :key="product.id">
                            <button 
                                @click="addToCart(product)"
                                class="bg-white dark:bg-gray-800 rounded-lg p-3 sm:p-4 shadow-sm hover:shadow-md active:shadow-inner transition border border-gray-200 dark:border-gray-700 text-left touch-btn"
                            >
                                <div class="flex flex-col h-full">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900 dark:text-white text-xs sm:text-sm mb-1 line-clamp-2" x-text="product.name"></h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 truncate" x-text="product.category?.name || 'No Category'"></p>
                                    </div>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-sm sm:text-lg font-bold text-blue-600 dark:text-blue-400" x-text="'₱' + parseFloat(product.price).toFixed(2)"></span>
                                        <span class="text-xs hidden sm:inline" 
                                              :class="getAvailableStock(product.id) <= 5 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-500 dark:text-gray-400'" 
                                              x-text="'Stock: ' + getAvailableStock(product.id)"></span>
                                    </div>
                                </div>
                            </button>
                        </template>
                    </div>
                    <div x-show="filteredProducts.length === 0" class="text-center py-12">
                        <p class="text-gray-500 dark:text-gray-400">No products found</p>
                    </div>
                </div>
                
                <!-- Mobile Bottom Cart Summary Bar -->
                <div 
                    x-show="cart.length > 0"
                    @click="showMobileCart = true"
                    class="lg:hidden fixed bottom-0 left-0 right-0 bg-blue-600 text-white p-4 flex items-center justify-between z-20 safe-area-bottom touch-btn"
                >
                    <div class="flex items-center gap-3">
                        <div class="bg-white/20 rounded-full w-8 h-8 flex items-center justify-center">
                            <span class="font-bold" x-text="cart.length"></span>
                        </div>
                        <span class="font-medium">View Cart</span>
                    </div>
                    <span class="text-xl font-bold" x-text="'₱' + total.toFixed(2)"></span>
                </div>
            </div>

            <!-- Cart Section (Desktop) -->
            <div class="hidden lg:flex w-96 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex-col">
                <!-- Cart Header -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Current Sale</h2>
                        <button
                            @click="openCustomItemModal()"
                            class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm rounded-lg transition touch-btn flex items-center gap-1"
                            title="Add Custom Item (not in inventory)"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Custom
                        </button>
                    </div>
                </div>

                <!-- Customer Selection -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Customer</label>
                    <div class="flex gap-2">
                        <select 
                            x-model="selectedCustomer"
                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">Walk-in Customer</option>
                            <template x-for="customer in customers" :key="customer.id">
                                <option :value="customer.id" x-text="customer.name"></option>
                            </template>
                        </select>
                        <button 
                            @click="showAddCustomerModal = true"
                            class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition touch-btn"
                            title="Add New Customer"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Cart Items -->
                <div class="flex-1 overflow-y-auto scroll-smooth p-4">
                    <template x-if="cart.length === 0">
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Cart is empty</p>
                        </div>
                    </template>

                    <div class="space-y-3">
                        <template x-for="(item, index) in cart" :key="index">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 relative">
                                <button 
                                    @click="removeFromCart(index)"
                                    class="absolute top-2 right-2 text-red-500 hover:text-red-700 touch-btn p-1"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                                <div class="flex items-center gap-2 mb-2 pr-6">
                                    <h4 class="font-medium text-gray-900 dark:text-white text-sm" x-text="item.name"></h4>
                                    <span x-show="item.is_manual" class="px-1.5 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-xs rounded font-medium">Custom</span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2" x-text="'₱' + parseFloat(item.unit_price).toFixed(2) + ' per ' + item.unit"></p>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <button 
                                            @click="updateQuantity(index, item.unit === 'piece' ? -1 : -0.1)"
                                            class="w-8 h-8 flex items-center justify-center bg-white dark:bg-gray-600 rounded border border-gray-300 dark:border-gray-500 hover:bg-gray-100 dark:hover:bg-gray-500 touch-btn"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                            </svg>
                                        </button>
                                        <span class="w-12 text-center font-medium dark:text-white text-sm" x-text="item.quantity.toFixed(item.unit === 'piece' ? 0 : 2)"></span>
                                        <button 
                                            @click="updateQuantity(index, item.unit === 'piece' ? 1 : 0.1)"
                                            class="w-8 h-8 flex items-center justify-center bg-white dark:bg-gray-600 rounded border border-gray-300 dark:border-gray-500 hover:bg-gray-100 dark:hover:bg-gray-500 touch-btn"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                    <span class="font-bold text-blue-600 dark:text-blue-400" x-text="'₱' + calculateItemPrice(item).toFixed(2)"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Cart Summary -->
                <div class="border-t border-gray-200 dark:border-gray-700 p-4 space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                        <span class="font-medium dark:text-white" x-text="'₱' + subtotal.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Tax (0%)</span>
                        <span class="font-medium dark:text-white" x-text="'₱' + tax.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-lg font-bold border-t border-gray-200 dark:border-gray-700 pt-3">
                        <span class="dark:text-white">Total</span>
                        <span class="text-blue-600 dark:text-blue-400" x-text="'₱' + total.toFixed(2)"></span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-2 pt-2">
                        <button 
                            @click="processPayment()"
                            :disabled="cart.length === 0 || isProcessing"
                            class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 dark:disabled:bg-gray-600 text-white font-semibold py-3 rounded-lg transition touch-btn"
                        >
                            <span x-show="!isProcessing">Complete Sale</span>
                            <span x-show="isProcessing">Processing...</span>
                        </button>
                        <button 
                            @click="showQuotationModal = true"
                            :disabled="cart.length === 0"
                            class="w-full bg-amber-500 hover:bg-amber-600 disabled:bg-gray-300 dark:disabled:bg-gray-600 text-white font-semibold py-3 rounded-lg transition touch-btn flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Create Quotation
                        </button>
                        <button 
                            @click="clearCart()"
                            :disabled="cart.length === 0"
                            class="w-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 disabled:bg-gray-100 dark:disabled:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold py-2 rounded-lg transition touch-btn"
                        >
                            Clear Cart
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Cart Slide-over -->
            <div 
                x-show="showMobileCart"
                x-cloak
                class="lg:hidden fixed inset-0 z-40"
                @keydown.escape.window="showMobileCart = false"
            >
                <!-- Backdrop -->
                <div 
                    x-show="showMobileCart"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute inset-0 bg-black/50"
                    @click="showMobileCart = false"
                ></div>
                
                <!-- Cart Panel -->
                <div 
                    x-show="showMobileCart"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full"
                    class="absolute right-0 top-0 bottom-0 w-full max-w-md bg-white dark:bg-gray-800 shadow-xl flex flex-col"
                >
                    <!-- Mobile Cart Header -->
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between safe-area-top">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Current Sale</h2>
                            <button
                                @click="openCustomItemModal()"
                                class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs rounded-lg transition touch-btn flex items-center gap-1"
                                title="Add Custom Item"
                            >
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Custom
                            </button>
                        </div>
                        <button
                            @click="showMobileCart = false"
                            class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 touch-btn"
                        >
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Customer Selection (Mobile) -->
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Customer</label>
                        <div class="flex gap-2">
                            <select 
                                x-model="selectedCustomer"
                                class="flex-1 px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-base"
                            >
                                <option value="">Walk-in Customer</option>
                                <template x-for="customer in customers" :key="customer.id">
                                    <option :value="customer.id" x-text="customer.name"></option>
                                </template>
                            </select>
                            <button 
                                @click="showAddCustomerModal = true"
                                class="px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition touch-btn"
                                title="Add New Customer"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Mobile Cart Items -->
                    <div class="flex-1 overflow-y-auto scroll-smooth p-4">
                        <template x-if="cart.length === 0">
                            <div class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Cart is empty</p>
                            </div>
                        </template>

                        <div class="space-y-3">
                            <template x-for="(item, index) in cart" :key="'mobile-' + index">
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 relative">
                                    <button 
                                        @click="removeFromCart(index)"
                                        class="absolute top-3 right-3 text-red-500 hover:text-red-700 touch-btn p-1"
                                    >
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <div class="flex items-center gap-2 mb-2 pr-8">
                                        <h4 class="font-medium text-gray-900 dark:text-white text-base" x-text="item.name"></h4>
                                        <span x-show="item.is_manual" class="px-1.5 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-xs rounded font-medium">Custom</span>
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3" x-text="'₱' + parseFloat(item.unit_price).toFixed(2) + ' per ' + item.unit"></p>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <button 
                                                @click="updateQuantity(index, item.unit === 'piece' ? -1 : -0.1)"
                                                class="w-10 h-10 flex items-center justify-center bg-white dark:bg-gray-600 rounded-lg border border-gray-300 dark:border-gray-500 touch-btn"
                                            >
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                                </svg>
                                            </button>
                                            <span class="w-14 text-center font-medium dark:text-white text-lg" x-text="item.quantity.toFixed(item.unit === 'piece' ? 0 : 2)"></span>
                                            <button 
                                                @click="updateQuantity(index, item.unit === 'piece' ? 1 : 0.1)"
                                                class="w-10 h-10 flex items-center justify-center bg-white dark:bg-gray-600 rounded-lg border border-gray-300 dark:border-gray-500 touch-btn"
                                            >
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </button>
                                        </div>
                                        <span class="font-bold text-lg text-blue-600 dark:text-blue-400" x-text="'₱' + calculateItemPrice(item).toFixed(2)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Mobile Cart Summary -->
                    <div class="border-t border-gray-200 dark:border-gray-700 p-4 space-y-3 safe-area-bottom">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                            <span class="font-medium dark:text-white" x-text="'₱' + subtotal.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Tax (0%)</span>
                            <span class="font-medium dark:text-white" x-text="'₱' + tax.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between text-xl font-bold border-t border-gray-200 dark:border-gray-700 pt-3">
                            <span class="dark:text-white">Total</span>
                            <span class="text-blue-600 dark:text-blue-400" x-text="'₱' + total.toFixed(2)"></span>
                        </div>

                        <!-- Mobile Action Buttons -->
                        <div class="space-y-3 pt-2">
                            <button 
                                @click="processPayment()"
                                :disabled="cart.length === 0 || isProcessing"
                                class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 dark:disabled:bg-gray-600 text-white font-semibold py-4 rounded-xl transition touch-btn text-lg"
                            >
                                <span x-show="!isProcessing">Complete Sale</span>
                                <span x-show="isProcessing">Processing...</span>
                            </button>
                            <button 
                                @click="showQuotationModal = true; showMobileCart = false"
                                :disabled="cart.length === 0"
                                class="w-full bg-amber-500 hover:bg-amber-600 disabled:bg-gray-300 dark:disabled:bg-gray-600 text-white font-semibold py-4 rounded-xl transition touch-btn text-lg flex items-center justify-center gap-2"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Create Quotation
                            </button>
                            <button 
                                @click="clearCart()"
                                :disabled="cart.length === 0"
                                class="w-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 disabled:bg-gray-100 dark:disabled:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold py-3 rounded-xl transition touch-btn"
                            >
                                Clear Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div 
        x-show="showPaymentModal" 
        x-cloak
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        @click.self="showPaymentModal = false"
    >
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Payment</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Total Amount</label>
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400" x-text="'₱' + total.toFixed(2)"></div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Method</label>
                        <select 
                            x-model="paymentMethod"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="cash">Cash</option>
                            <option value="cod">Cash on Delivery (COD)</option>
                            <option value="card">Card</option>
                            <option value="gcash">GCash</option>
                            <option value="paymaya">PayMaya</option>
                        </select>
                    </div>

                    <div x-show="paymentMethod === 'cod'" x-cloak class="space-y-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="codWithTerms" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Add Payment Terms</span>
                        </label>

                        <div x-show="codWithTerms" x-cloak>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Terms</label>
                            <div class="grid grid-cols-4 gap-2">
                                <template x-for="days in [5, 10, 15, 30, 60]" :key="days">
                                    <button
                                        type="button"
                                        @click="paymentTermDays = days"
                                        :class="paymentTermDays === days
                                            ? 'bg-blue-600 text-white border-blue-600'
                                            : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'"
                                        class="px-3 py-2 border rounded-lg text-sm font-medium transition"
                                        x-text="days + ' Days'"
                                    ></button>
                                </template>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                Due date: <span class="font-medium" x-text="new Date(Date.now() + paymentTermDays * 86400000).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })"></span>
                            </p>
                        </div>
                    </div>

                    <div x-show="paymentMethod === 'cash'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cash Received</label>
                        <input 
                            type="number" 
                            x-model.number="cashReceived"
                            @input="calculateChange()"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        >
                        <div class="mt-2 flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Change</span>
                            <span class="font-bold" :class="change < 0 ? 'text-red-600' : 'text-green-600'" x-text="'₱' + Math.abs(change).toFixed(2)"></span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button 
                        @click="showPaymentModal = false"
                        class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="confirmPayment()"
                        :disabled="paymentMethod === 'cash' && change < 0"
                        class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 dark:disabled:bg-gray-600 text-white rounded-lg transition"
                    >
                        Confirm Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Weight Selector Modal -->
    <div 
        x-show="showWeightModal" 
        x-cloak
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        @click.self="showWeightModal = false"
    >
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Select Quantity</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Product</label>
                        <div class="text-lg font-semibold text-gray-900 dark:text-white" x-text="pendingProduct?.name"></div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Quantity</label>
                        <div class="flex gap-2 mb-3">
                            <input 
                                type="number" 
                                x-model.number="weightValue"
                                @input="calculateWeightPrice()"
                                step="0.1"
                                min="0.1"
                                placeholder="Enter quantity"
                                class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            >
                            <select 
                                x-model="weightUnit"
                                @change="calculateWeightPrice()"
                                class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            >
                                <!-- Kilo conversions -->
                                <template x-if="pendingProduct?.unit === 'kilo'">
                                    <option value="gram">Grams</option>
                                </template>
                                <template x-if="pendingProduct?.unit === 'kilo'">
                                    <option value="kilo" selected>Kilos</option>
                                </template>
                                
                                <!-- Liter conversions -->
                                <template x-if="pendingProduct?.unit === 'liter'">
                                    <option value="milliliter">Milliliters</option>
                                </template>
                                <template x-if="pendingProduct?.unit === 'liter'">
                                    <option value="liter" selected>Liters</option>
                                </template>

                                <!-- Meter conversions -->
                                <template x-if="pendingProduct?.unit === 'meter'">
                                    <option value="foot">Feet</option>
                                </template>
                                <template x-if="pendingProduct?.unit === 'meter'">
                                    <option value="meter" selected>Meters</option>
                                </template>

                                <!-- Foot conversions -->
                                <template x-if="pendingProduct?.unit === 'foot'">
                                    <option value="foot" selected>Feet</option>
                                </template>
                                <template x-if="pendingProduct?.unit === 'foot'">
                                    <option value="meter">Meters</option>
                                </template>

                                <!-- No conversion options for package units -->
                                <template x-if="pendingProduct?.unit === 'bag' || pendingProduct?.unit === 'box' || pendingProduct?.unit === 'bundle' || pendingProduct?.unit === 'tube' || pendingProduct?.unit === 'knot'">
                                    <option :value="pendingProduct?.unit" selected x-text="pendingProduct?.unit.charAt(0).toUpperCase() + pendingProduct?.unit.slice(1)"></option>
                                </template>

                                <!-- Cubic meter has no conversion -->
                                <template x-if="pendingProduct?.unit === 'cubic_meter'">
                                    <option value="cubic_meter" selected>Cubic Meters</option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600 dark:text-gray-400">Unit Price</span>
                            <span class="font-semibold dark:text-white" x-text="'₱' + parseFloat(pendingProduct?.price).toFixed(2) + ' per ' + (pendingProduct?.unit === 'cubic_meter' ? 'cu.m' : pendingProduct?.unit)"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Total Price</span>
                            <span class="font-bold text-lg text-blue-600 dark:text-blue-400" x-text="'₱' + calculatedWeightPrice.toFixed(2)"></span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button 
                        @click="showWeightModal = false; pendingProduct = null"
                        class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="addPendingProductToCart()"
                        class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition"
                    >
                        Add to Cart
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div
        x-show="showSuccessModal"
        x-cloak
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
    >
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Sale Complete!</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">The transaction was successful</p>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Paid</div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="'₱' + lastSaleTotal.toFixed(2)"></div>
                </div>

                <!-- Print Receipt Options -->
                <div class="space-y-2 mb-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Print Receipt?</p>
                    <div class="flex gap-2">
                        <button
                            @click="printReceipt('delivery')"
                            class="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-semibold flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            For Delivery
                        </button>
                        <button
                            @click="printReceipt('pickup')"
                            class="flex-1 px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition font-semibold flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            For Pick Up
                        </button>
                    </div>
                </div>

                <button
                    @click="showSuccessModal = false"
                    class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                >
                    Skip & New Sale
                </button>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div 
        x-show="showAddCustomerModal" 
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        @click.self="showAddCustomerModal = false"
    >
        <div 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-4"
        >
            <div class="p-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Add New Customer</h3>
                    <button 
                        @click="showAddCustomerModal = false"
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 touch-btn rounded-lg"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="saveCustomer()" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name <span class="text-red-500">*</span></label>
                        <input 
                            type="text" 
                            x-model="newCustomer.name"
                            required
                            placeholder="Customer name"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-base"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                        <input 
                            type="tel" 
                            x-model="newCustomer.phone"
                            placeholder="Phone number"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-base"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                        <input 
                            type="email" 
                            x-model="newCustomer.email"
                            placeholder="Email address"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-base"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address</label>
                        <textarea 
                            x-model="newCustomer.address"
                            placeholder="Customer address"
                            rows="2"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-base resize-none"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Company</label>
                        <input 
                            type="text" 
                            x-model="newCustomer.company"
                            placeholder="Company name"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-base"
                        >
                    </div>

                    <!-- Error Message -->
                    <div x-show="customerError" class="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 p-3 rounded-lg text-sm" x-text="customerError"></div>

                    <!-- Buttons -->
                    <div class="flex gap-3 pt-2">
                        <button 
                            type="button"
                            @click="showAddCustomerModal = false; resetCustomerForm()"
                            class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition font-semibold touch-btn"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit"
                            :disabled="isSavingCustomer || !newCustomer.name"
                            class="flex-1 px-4 py-3 bg-green-600 hover:bg-green-700 disabled:bg-gray-300 dark:disabled:bg-gray-600 text-white rounded-xl transition font-semibold touch-btn"
                        >
                            <span x-show="!isSavingCustomer">Save Customer</span>
                            <span x-show="isSavingCustomer">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Out of Stock Modal -->
    <div 
        x-show="showOutOfStockModal" 
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        @click.self="showOutOfStockModal = false"
    >
        <div 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full mx-4 transform"
        >
            <div class="p-6">
                <!-- Icon -->
                <div class="flex items-center justify-center w-20 h-20 mx-auto mb-4 bg-red-100 dark:bg-red-900/30 rounded-full">
                    <svg class="w-10 h-10 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4M12 4v16m0-16L8 8m4-4l4 4" transform="rotate(45 12 12)" />
                    </svg>
                </div>

                <!-- Title -->
                <h3 class="text-xl font-bold text-gray-900 dark:text-white text-center mb-2" x-text="outOfStockTitle"></h3>
                
                <!-- Message -->
                <p class="text-gray-600 dark:text-gray-400 text-center mb-2" x-text="outOfStockMessage"></p>
                
                <!-- Product Info -->
                <div x-show="outOfStockProduct" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 mb-6">
                    <p class="text-sm font-medium text-gray-900 dark:text-white text-center" x-text="outOfStockProduct"></p>
                </div>

                <!-- Button -->
                <button 
                    @click="showOutOfStockModal = false"
                    class="w-full px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl transition font-semibold"
                >
                    Got it
                </button>
            </div>
        </div>
    </div>

    <!-- Clear Cart Confirmation Modal -->
    <div 
        x-show="showClearCartModal" 
        x-cloak
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        @click.self="showClearCartModal = false"
    >
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 transform transition-all">
            <div class="p-6">
                <!-- Icon -->
                <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-yellow-100 dark:bg-yellow-900/30 rounded-full">
                    <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                <!-- Title -->
                <h3 class="text-xl font-bold text-gray-900 dark:text-white text-center mb-2">Clear Cart?</h3>
                
                <!-- Message -->
                <p class="text-gray-600 dark:text-gray-400 text-center mb-6">
                    Are you sure you want to clear the cart? This action cannot be undone!
                </p>

                <!-- Cart Summary -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Items in cart:</span>
                        <span class="font-semibold text-gray-900 dark:text-white" x-text="cart.length"></span>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Total amount:</span>
                        <span class="font-bold text-lg text-blue-600 dark:text-blue-400" x-text="'₱' + total.toFixed(2)"></span>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex gap-3">
                    <button 
                        @click="showClearCartModal = false"
                        class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition font-semibold"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="confirmClearCart()"
                        class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-semibold"
                    >
                        Clear Cart
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quotation Modal -->
    <div 
        x-show="showQuotationModal" 
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        @click.self="showQuotationModal = false"
    >
        <div 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto"
        >
            <div class="p-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Create Quotation</h3>
                    </div>
                    <button 
                        @click="showQuotationModal = false"
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 touch-btn rounded-lg"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Customer Info -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Customer</div>
                    <div class="font-semibold text-gray-900 dark:text-white" x-text="selectedCustomer ? customers.find(c => c.id == selectedCustomer)?.name : 'Walk-in Customer'"></div>
                </div>

                <!-- Items Summary -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Items (<span x-text="cart.length"></span>)</div>
                    <div class="max-h-40 overflow-y-auto space-y-2">
                        <template x-for="(item, index) in cart" :key="'quot-item-' + index">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-700 dark:text-gray-300">
                                    <span x-text="item.name + ' × ' + item.quantity.toFixed(item.unit === 'piece' ? 0 : 2)"></span>
                                    <span x-show="item.is_manual" class="ml-1 px-1 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-xs rounded">Custom</span>
                                </span>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="'₱' + calculateItemPrice(item).toFixed(2)"></span>
                            </div>
                        </template>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-600 mt-3 pt-3">
                        <div class="flex justify-between">
                            <span class="font-semibold text-gray-900 dark:text-white">Total</span>
                            <span class="font-bold text-lg text-amber-600 dark:text-amber-400" x-text="'₱' + total.toFixed(2)"></span>
                        </div>
                    </div>
                </div>

                <!-- Quotation Options -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valid For (Days)</label>
                        <select 
                            x-model="quotationValidDays"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-white text-base"
                        >
                            <option value="7">7 Days</option>
                            <option value="15">15 Days</option>
                            <option value="30" selected>30 Days</option>
                            <option value="60">60 Days</option>
                            <option value="90">90 Days</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes (Optional)</label>
                        <textarea 
                            x-model="quotationNotes"
                            placeholder="Add any notes or terms for this quotation..."
                            rows="3"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-white text-base resize-none"
                        ></textarea>
                    </div>
                </div>

                <!-- Error Message -->
                <div x-show="quotationError" class="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 p-3 rounded-lg text-sm mt-4" x-text="quotationError"></div>

                <!-- Buttons -->
                <div class="flex gap-3 mt-6">
                    <button 
                        @click="showQuotationModal = false"
                        class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition font-semibold touch-btn"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="createQuotation()"
                        :disabled="isCreatingQuotation || cart.length === 0"
                        class="flex-1 px-4 py-3 bg-amber-500 hover:bg-amber-600 disabled:bg-gray-300 dark:disabled:bg-gray-600 text-white rounded-xl transition font-semibold touch-btn flex items-center justify-center gap-2"
                    >
                        <span x-show="!isCreatingQuotation">Create & Print</span>
                        <span x-show="isCreatingQuotation">Creating...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quotation Success Modal -->
    <div
        x-show="showQuotationSuccessModal"
        x-cloak
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
    >
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-4">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Quotation Created!</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-2">Your quotation has been saved successfully</p>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Quotation Number</div>
                    <div class="text-lg font-bold text-amber-600 dark:text-amber-400" x-text="lastQuotationNumber"></div>
                </div>
                <div class="flex gap-3">
                    <button
                        @click="printQuotation()"
                        class="flex-1 px-4 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl transition font-semibold flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print
                    </button>
                    <button
                        @click="showQuotationSuccessModal = false"
                        class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition font-semibold"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Item Modal -->
    <div
        x-show="showCustomItemModal"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        @click.self="showCustomItemModal = false"
        @keydown.escape.window="showCustomItemModal = false"
    >
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto"
        >
            <div class="p-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Add Custom Item</h3>
                    </div>
                    <button
                        @click="showCustomItemModal = false"
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 touch-btn rounded-lg"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Add an item that is not in the product inventory. This is only available for quotations.</p>

                <!-- Form Fields -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Item Description *</label>
                        <input
                            type="text"
                            x-model="customItem.name"
                            placeholder="Enter item name or description"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white text-base"
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity *</label>
                            <input
                                type="number"
                                x-model="customItem.quantity"
                                @input="calculateCustomItemPrice()"
                                min="0.01"
                                step="0.01"
                                placeholder="1"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white text-base"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unit *</label>
                            <select
                                x-model="customItem.unit"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white text-base"
                            >
                                <option value="piece">Piece</option>
                                <option value="liter">Liter</option>
                                <option value="milliliter">Milliliter</option>
                                <option value="kilo">Kilo</option>
                                <option value="gram">Gram</option>
                                <option value="foot">Foot</option>
                                <option value="meter">Meter</option>
                                <option value="cubic_meter">Cubic Meter</option>
                                <option value="bag">Bag</option>
                                <option value="knot">Knot</option>
                                <option value="bundle">Bundle</option>
                                <option value="box">Box</option>
                                <option value="tube">Tube</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unit Price (₱) *</label>
                        <input
                            type="number"
                            x-model="customItem.unit_price"
                            @input="calculateCustomItemPrice()"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white text-base"
                        >
                    </div>

                    <!-- Calculated Total -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">Line Total</span>
                            <span class="text-xl font-bold text-emerald-600 dark:text-emerald-400" x-text="'₱' + customItem.total.toFixed(2)"></span>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <div x-show="customItemError" class="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 p-3 rounded-lg text-sm mt-4" x-text="customItemError"></div>

                <!-- Buttons -->
                <div class="flex gap-3 mt-6">
                    <button
                        @click="showCustomItemModal = false"
                        class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition font-semibold touch-btn"
                    >
                        Cancel
                    </button>
                    <button
                        @click="addCustomItemToCart()"
                        class="flex-1 px-4 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl transition font-semibold touch-btn flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add to Cart
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reprint Modal -->
    <div
        x-show="showReprintModal"
        x-cloak
        @keydown.escape.window="showReprintModal = false"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        @click.self="showReprintModal = false"
    >
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[85vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Reprint Receipt</h3>
                    </div>
                    <button
                        @click="showReprintModal = false"
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 touch-btn rounded-lg"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Search -->
                <div class="mt-4">
                    <input
                        type="text"
                        x-model="reprintSearchQuery"
                        @input="searchRecentSales()"
                        placeholder="Search by receipt # or customer name..."
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white text-base"
                    >
                </div>
            </div>

            <!-- Sales List -->
            <div class="flex-1 overflow-y-auto p-6">
                <div x-show="isLoadingRecentSales" class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Loading recent sales...</p>
                </div>

                <div x-show="!isLoadingRecentSales && filteredRecentSales.length === 0" class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">No recent sales found</p>
                </div>

                <div class="space-y-3">
                    <template x-for="sale in filteredRecentSales" :key="sale.id">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-600 transition cursor-pointer border border-transparent hover:border-purple-500"
                             @click="selectedSaleForReprint = sale; showReprintTypeModal = true">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-semibold text-gray-900 dark:text-white" x-text="'Receipt #' + String(sale.id).padStart(6, '0')"></span>
                                        <span class="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded">Sale</span>
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                        <span class="font-medium">Customer:</span>
                                        <span x-text="sale.customer ? sale.customer.name : 'Walk-in Customer'"></span>
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                        <span class="font-medium">Date:</span>
                                        <span x-text="new Date(sale.date).toLocaleString('en-US', {month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'})"></span>
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        <span class="font-medium">Items:</span>
                                        <span x-text="sale.sale_items_count + ' item(s)'"></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-purple-600 dark:text-purple-400" x-text="'₱' + parseFloat(sale.total).toFixed(2)"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                <button
                    @click="showReprintModal = false"
                    class="w-full px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition font-semibold"
                >
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Reprint Type Selection Modal -->
    <div
        x-show="showReprintTypeModal"
        x-cloak
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-[60]"
        @click.self="showReprintTypeModal = false"
    >
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Select Receipt Type</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Receipt <span x-text="selectedSaleForReprint ? '#' + String(selectedSaleForReprint.id).padStart(6, '0') : ''"></span>
                </p>

                <!-- Receipt Type Options -->
                <div class="space-y-2 mb-4">
                    <button
                        @click="reprintReceipt('delivery')"
                        class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-semibold flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Print For Delivery
                    </button>
                    <button
                        @click="reprintReceipt('pickup')"
                        class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition font-semibold flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        Print For Pick Up
                    </button>
                </div>

                <button
                    @click="showReprintTypeModal = false"
                    class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                >
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
        function posSystem() {
            return {
                products: @json($products),
                customers: @json($customers),
                filteredProducts: [],
                cart: [],
                searchQuery: '',
                selectedCategory: '',
                selectedCustomer: '',
                showPaymentModal: false,
                showSuccessModal: false,
                showClearCartModal: false,
                showWeightModal: false,
                showOutOfStockModal: false,
                showMobileCart: false,
                showAddCustomerModal: false,
                showQuotationModal: false,
                showQuotationSuccessModal: false,
                showReprintModal: false,
                showReprintTypeModal: false,
                outOfStockTitle: '',
                outOfStockMessage: '',
                outOfStockProduct: '',
                recentSales: [],
                filteredRecentSales: [],
                reprintSearchQuery: '',
                isLoadingRecentSales: false,
                selectedSaleForReprint: null,
                isProcessing: false,
                isCreatingQuotation: false,
                isSavingCustomer: false,
                customerError: '',
                quotationError: '',
                quotationNotes: '',
                quotationValidDays: 30,
                lastQuotationNumber: '',
                lastQuotationPrintUrl: '',
                lastSaleId: null,
                newCustomer: {
                    name: '',
                    phone: '',
                    email: '',
                    address: ''
                },
                paymentMethod: 'cash',
                codWithTerms: false,
                paymentTermDays: 5,
                cashReceived: 0,
                change: 0,
                lastSaleTotal: 0,
                currentDateTime: '',
                pendingProduct: null,
                weightValue: 0,
                weightUnit: 'kilo',
                calculatedWeightPrice: 0,
                showCustomItemModal: false,
                customItemError: '',
                customItem: {
                    name: '',
                    quantity: 1,
                    unit: 'piece',
                    unit_price: 0,
                    total: 0
                },

                init() {
                    this.filteredProducts = this.products;
                    this.updateDateTime();
                    setInterval(() => this.updateDateTime(), 1000);

                    // Watch for reprint modal opening
                    this.$watch('showReprintModal', (value) => {
                        if (value && this.recentSales.length === 0) {
                            this.fetchRecentSales();
                        }
                    });
                },

                getAvailableStock(productId) {
                    const product = this.products.find(p => p.id === productId);
                    if (!product) return 0;
                    
                    const originalStock = product.inventory?.quantity || 0;
                    const cartItem = this.cart.find(item => item.id === productId);
                    const quantityInCart = cartItem ? cartItem.quantity : 0;
                    
                    return originalStock - quantityInCart;
                },

                updateDateTime() {
                    const now = new Date();
                    this.currentDateTime = now.toLocaleString('en-PH', {
                        timeZone: 'Asia/Manila',
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                },

                filterProducts() {
                    this.filteredProducts = this.products.filter(product => {
                        const matchesSearch = product.name.toLowerCase().includes(this.searchQuery.toLowerCase());
                        const matchesCategory = !this.selectedCategory || product.category_id == this.selectedCategory;
                        return matchesSearch && matchesCategory;
                    });
                },

                addToCart(product) {
                    // For weight-based products, show weight selector
                    if (product.unit !== 'piece') {
                        this.pendingProduct = product;
                        this.weightValue = 1;
                        this.weightUnit = product.unit;
                        this.calculatedWeightPrice = product.price;
                        this.showWeightModal = true;
                        return;
                    }

                    // For piece-based products, add directly
                    const existingItem = this.cart.find(item => item.id === product.id);
                    const availableStock = this.getAvailableStock(product.id);
                    
                    if (existingItem) {
                        if (availableStock > 0) {
                            existingItem.quantity += 1;
                        } else {
                            this.showStockAlert('Not Enough Stock', 'There is not enough stock available to add more of this item.', product.name);
                        }
                    } else {
                        if (availableStock > 0) {
                            this.cart.push({
                                id: product.id,
                                name: product.name,
                                price: parseFloat(product.price),
                                unit_price: parseFloat(product.price),
                                unit: product.unit,
                                quantity: 1,
                                maxStock: product.inventory?.quantity || 0
                            });
                        } else {
                            this.showStockAlert('Out of Stock', 'This product is currently out of stock and cannot be added to cart.', product.name);
                        }
                    }
                },

                showStockAlert(title, message, productName = '') {
                    this.outOfStockTitle = title;
                    this.outOfStockMessage = message;
                    this.outOfStockProduct = productName;
                    this.showOutOfStockModal = true;
                    
                    // Speak the alert using Web Speech API
                    if ('speechSynthesis' in window) {
                        const utterance = new SpeechSynthesisUtterance(productName ? `${title}. ${productName}` : title);
                        utterance.rate = 1;
                        utterance.pitch = 1;
                        utterance.volume = 1;
                        speechSynthesis.speak(utterance);
                    }
                },

                resetCustomerForm() {
                    this.newCustomer = {
                        name: '',
                        phone: '',
                        email: '',
                        address: ''
                    };
                    this.customerError = '';
                },

                async saveCustomer() {
                    if (!this.newCustomer.name.trim()) {
                        this.customerError = 'Customer name is required';
                        return;
                    }

                    this.isSavingCustomer = true;
                    this.customerError = '';

                    try {
                        const response = await fetch('/pos/customer', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.newCustomer)
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Add the new customer to the list
                            this.customers.push(data.customer);
                            // Select the new customer
                            this.selectedCustomer = data.customer.id;
                            // Close modal and reset form
                            this.showAddCustomerModal = false;
                            this.resetCustomerForm();
                        } else {
                            this.customerError = data.message || 'Failed to add customer';
                        }
                    } catch (error) {
                        this.customerError = 'Error adding customer: ' + error.message;
                    } finally {
                        this.isSavingCustomer = false;
                    }
                },

                calculateWeightPrice() {
                    if (!this.pendingProduct || !this.weightValue) {
                        this.calculatedWeightPrice = 0;
                        return;
                    }

                    const basePrice = parseFloat(this.pendingProduct.price);
                    let quantity = this.weightValue;

                    // Convert to base unit if needed
                    if (this.pendingProduct.unit === 'kilo' && this.weightUnit === 'gram') {
                        quantity = this.weightValue / 1000;
                    } else if (this.pendingProduct.unit === 'liter' && this.weightUnit === 'milliliter') {
                        quantity = this.weightValue / 1000;
                    } else if (this.pendingProduct.unit === 'meter' && this.weightUnit === 'foot') {
                        quantity = this.weightValue / 3.28084;
                    } else if (this.pendingProduct.unit === 'foot' && this.weightUnit === 'meter') {
                        quantity = this.weightValue * 3.28084;
                    }

                    this.calculatedWeightPrice = basePrice * quantity;
                },

                addPendingProductToCart() {
                    if (!this.pendingProduct || !this.weightValue) {
                        alert('Please enter a valid quantity');
                        return;
                    }

                    const availableStock = this.getAvailableStock(this.pendingProduct.id);
                    let quantity = this.weightValue;

                    // Convert to base unit if needed
                    if (this.pendingProduct.unit === 'kilo' && this.weightUnit === 'gram') {
                        quantity = this.weightValue / 1000;
                    } else if (this.pendingProduct.unit === 'liter' && this.weightUnit === 'milliliter') {
                        quantity = this.weightValue / 1000;
                    } else if (this.pendingProduct.unit === 'meter' && this.weightUnit === 'foot') {
                        quantity = this.weightValue / 3.28084;
                    } else if (this.pendingProduct.unit === 'foot' && this.weightUnit === 'meter') {
                        quantity = this.weightValue * 3.28084;
                    }

                    if (quantity > availableStock) {
                        alert('Not enough stock available');
                        return;
                    }

                    const existingItem = this.cart.find(item => item.id === this.pendingProduct.id);
                    
                    if (existingItem) {
                        existingItem.quantity += quantity;
                    } else {
                        this.cart.push({
                            id: this.pendingProduct.id,
                            name: this.pendingProduct.name,
                            price: parseFloat(this.pendingProduct.price),
                            unit_price: parseFloat(this.pendingProduct.price),
                            unit: this.pendingProduct.unit,
                            quantity: quantity,
                            maxStock: this.pendingProduct.inventory?.quantity || 0
                        });
                    }

                    this.showWeightModal = false;
                    this.pendingProduct = null;
                    this.weightValue = 0;
                    this.calculatedWeightPrice = 0;
                },

                updateQuantity(index, change) {
                    const item = this.cart[index];
                    const newQuantity = item.quantity + change;
                    
                    if (newQuantity <= 0) {
                        this.removeFromCart(index);
                    } else {
                        const availableStock = this.getAvailableStock(item.id) + item.quantity;
                        
                        if (newQuantity <= availableStock) {
                            item.quantity = newQuantity;
                        } else {
                            alert('Not enough stock available');
                        }
                    }
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                },

                calculateItemPrice(item) {
                    // For weight/liquid units (kilo, gram, liter, milliliter), 
                    // calculate price proportionally based on quantity
                    return item.unit_price * item.quantity;
                },

                clearCart() {
                    if (this.cart.length > 0) {
                        this.showClearCartModal = true;
                    }
                },

                confirmClearCart() {
                    this.cart = [];
                    this.showClearCartModal = false;
                },

                calculateChange() {
                    this.change = this.cashReceived - this.total;
                },

                processPayment() {
                    this.showPaymentModal = true;
                    this.cashReceived = this.total;
                    this.calculateChange();
                },

                async confirmPayment() {
                    this.isProcessing = true;
                    
                    try {
                        const response = await fetch('/pos/complete-sale', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                customer_id: this.selectedCustomer || null,
                                items: this.cart,
                                total: this.total,
                                payment_method: this.paymentMethod,
                                payment_term_days: (this.paymentMethod === 'cod' && this.codWithTerms) ? this.paymentTermDays : null,
                                cash_received: this.cashReceived,
                                change: this.change
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.lastSaleTotal = this.total;
                            this.lastSaleId = data.sale_id;
                            this.showPaymentModal = false;
                            this.showSuccessModal = true;
                            this.cart = [];
                            this.selectedCustomer = '';
                            this.paymentMethod = 'cash';
                            this.codWithTerms = false;
                            this.paymentTermDays = 5;
                            this.cashReceived = 0;
                            this.change = 0;
                        } else {
                            alert('Error processing sale: ' + (data.message || 'Unknown error'));
                        }
                    } catch (error) {
                        alert('Error processing sale: ' + error.message);
                    } finally {
                        this.isProcessing = false;
                    }
                },

                async createQuotation() {
                    if (this.cart.length === 0) {
                        this.quotationError = 'Cart is empty';
                        return;
                    }

                    this.isCreatingQuotation = true;
                    this.quotationError = '';

                    try {
                        const response = await fetch('/pos/quotation', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                customer_id: this.selectedCustomer || null,
                                items: this.cart,
                                total: this.total,
                                notes: this.quotationNotes,
                                valid_days: parseInt(this.quotationValidDays)
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.lastQuotationNumber = data.quotation_number;
                            this.lastQuotationPrintUrl = data.print_url;
                            this.showQuotationModal = false;
                            this.showQuotationSuccessModal = true;
                            this.cart = [];
                            this.selectedCustomer = '';
                            this.quotationNotes = '';
                            this.quotationValidDays = 30;
                        } else {
                            this.quotationError = data.message || 'Failed to create quotation';
                        }
                    } catch (error) {
                        this.quotationError = 'Error creating quotation: ' + error.message;
                    } finally {
                        this.isCreatingQuotation = false;
                    }
                },

                printQuotation() {
                    if (this.lastQuotationPrintUrl) {
                        window.open(this.lastQuotationPrintUrl, '_blank');
                    }
                },

                openCustomItemModal() {
                    this.customItem = {
                        name: '',
                        quantity: 1,
                        unit: 'piece',
                        unit_price: 0,
                        total: 0
                    };
                    this.customItemError = '';
                    this.showCustomItemModal = true;
                },

                calculateCustomItemPrice() {
                    const quantity = parseFloat(this.customItem.quantity) || 0;
                    const unitPrice = parseFloat(this.customItem.unit_price) || 0;
                    this.customItem.total = quantity * unitPrice;
                },

                addCustomItemToCart() {
                    // Validate
                    if (!this.customItem.name.trim()) {
                        this.customItemError = 'Item description is required';
                        return;
                    }
                    if (!this.customItem.quantity || this.customItem.quantity <= 0) {
                        this.customItemError = 'Valid quantity is required';
                        return;
                    }
                    if (!this.customItem.unit_price || this.customItem.unit_price < 0) {
                        this.customItemError = 'Valid unit price is required';
                        return;
                    }

                    // Add to cart with is_manual flag
                    this.cart.push({
                        id: null,
                        is_manual: true,
                        name: this.customItem.name.trim(),
                        price: this.customItem.total,
                        unit_price: parseFloat(this.customItem.unit_price),
                        unit: this.customItem.unit,
                        quantity: parseFloat(this.customItem.quantity),
                        maxStock: Infinity
                    });

                    // Close modal and reset
                    this.showCustomItemModal = false;
                    this.customItem = {
                        name: '',
                        quantity: 1,
                        unit: 'piece',
                        unit_price: 0,
                        total: 0
                    };
                    this.customItemError = '';
                },

                printReceipt(type) {
                    if (this.lastSaleId) {
                        const printUrl = `/pos/print-receipt/${this.lastSaleId}?type=${type}`;
                        window.open(printUrl, '_blank');
                        this.showSuccessModal = false;
                    }
                },

                async fetchRecentSales() {
                    this.isLoadingRecentSales = true;
                    try {
                        const response = await fetch('/pos/recent-sales', {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.recentSales = data.sales;
                            this.filteredRecentSales = data.sales;
                        }
                    } catch (error) {
                        console.error('Error fetching recent sales:', error);
                    } finally {
                        this.isLoadingRecentSales = false;
                    }
                },

                searchRecentSales() {
                    const query = this.reprintSearchQuery.toLowerCase();
                    if (!query) {
                        this.filteredRecentSales = this.recentSales;
                        return;
                    }

                    this.filteredRecentSales = this.recentSales.filter(sale => {
                        const receiptNumber = String(sale.id).padStart(6, '0');
                        const customerName = sale.customer ? sale.customer.name.toLowerCase() : 'walk-in customer';
                        return receiptNumber.includes(query) || customerName.includes(query);
                    });
                },

                reprintReceipt(type) {
                    if (this.selectedSaleForReprint) {
                        const printUrl = `/pos/print-receipt/${this.selectedSaleForReprint.id}?type=${type}`;
                        window.open(printUrl, '_blank');
                        this.showReprintTypeModal = false;
                        this.showReprintModal = false;
                    }
                },

                get subtotal() {
                    return this.cart.reduce((sum, item) => sum + this.calculateItemPrice(item), 0);
                },

                get tax() {
                    return this.subtotal * 0; // 0% tax, adjust as needed
                },

                get total() {
                    return this.subtotal + this.tax;
                }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</body>
</html>
