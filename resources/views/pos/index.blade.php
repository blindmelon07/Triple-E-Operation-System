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
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Current Sale</h2>
                </div>

                <!-- Customer Selection -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Customer</label>
                    <select 
                        x-model="selectedCustomer"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">Walk-in Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
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
                                <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-2 pr-6" x-text="item.name"></h4>
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
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Current Sale</h2>
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
                        <select 
                            x-model="selectedCustomer"
                            class="w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-base"
                        >
                            <option value="">Walk-in Customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
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
                                    <h4 class="font-medium text-gray-900 dark:text-white text-base mb-2 pr-8" x-text="item.name"></h4>
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
                            <option value="card">Card</option>
                            <option value="gcash">GCash</option>
                            <option value="paymaya">PayMaya</option>
                        </select>
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
                <button 
                    @click="showSuccessModal = false"
                    class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition"
                >
                    New Sale
                </button>
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
                    Are you sure you want to clear the cart? This action cannot be undone.
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

    <script>
        function posSystem() {
            return {
                products: @json($products),
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
                outOfStockTitle: '',
                outOfStockMessage: '',
                outOfStockProduct: '',
                isProcessing: false,
                paymentMethod: 'cash',
                cashReceived: 0,
                change: 0,
                lastSaleTotal: 0,
                currentDateTime: '',
                pendingProduct: null,
                weightValue: 0,
                weightUnit: 'kilo',
                calculatedWeightPrice: 0,

                init() {
                    this.filteredProducts = this.products;
                    this.updateDateTime();
                    setInterval(() => this.updateDateTime(), 1000);
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
                    this.currentDateTime = now.toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
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
                                cash_received: this.cashReceived,
                                change: this.change
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.lastSaleTotal = this.total;
                            this.showPaymentModal = false;
                            this.showSuccessModal = true;
                            this.cart = [];
                            this.selectedCustomer = '';
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
