<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farm Management System - Welcome</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-50 min-h-screen">
    <div class="container mx-auto px-4 py-16">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <div class="inline-block bg-green-600 text-white p-4 rounded-full mb-4">
                    <i class="fas fa-tractor text-4xl"></i>
                </div>
                <h1 class="text-4xl font-bold text-gray-800 mb-4">Farm Management System</h1>
                <p class="text-xl text-gray-600">Complete CRUD Application with Database Integration</p>
                <div class="mt-4 inline-block bg-blue-100 text-blue-800 px-4 py-2 rounded-full text-sm font-semibold">
                    <i class="fas fa-check-circle mr-2"></i>Version 1.0 - Production Ready
                </div>
            </div>

            <!-- Quick Access Cards -->
            <div class="grid md:grid-cols-2 gap-6 mb-12">
                <a href="dashboard.php" class="block bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition transform hover:-translate-y-1">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-chart-line text-blue-600 text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Dashboard</h2>
                            <p class="text-gray-600 text-sm">View reports & analytics</p>
                        </div>
                    </div>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li><i class="fas fa-check text-green-600 mr-2"></i>Interactive charts & KPIs</li>
                        <li><i class="fas fa-check text-green-600 mr-2"></i>Month/Year filtering</li>
                        <li><i class="fas fa-check text-green-600 mr-2"></i>Real-time data from database</li>
                    </ul>
                </a>

                <a href="admin.php" class="block bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition transform hover:-translate-y-1">
                    <div class="flex items-center mb-4">
                        <div class="bg-green-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-cog text-green-600 text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Admin Panel</h2>
                            <p class="text-gray-600 text-sm">Manage all farm data</p>
                        </div>
                    </div>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li><i class="fas fa-check text-green-600 mr-2"></i>Complete CRUD operations</li>
                        <li><i class="fas fa-check text-green-600 mr-2"></i>Add, Edit, Delete records</li>
                        <li><i class="fas fa-check text-green-600 mr-2"></i>Manage all categories</li>
                    </ul>
                </a>
            </div>

            <!-- Features -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
                    <i class="fas fa-star text-yellow-500 mr-2"></i>Key Features
                </h2>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-database text-purple-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-2">Full Database</h3>
                        <p class="text-sm text-gray-600">MySQL with 6 tables, sample data included</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-sync-alt text-blue-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-2">Complete CRUD</h3>
                        <p class="text-sm text-gray-600">Create, Read, Update, Delete all data</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-calendar-alt text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-2">Monthly Data</h3>
                        <p class="text-sm text-gray-600">Organized by year and month</p>
                    </div>
                </div>
            </div>

            <!-- Data Categories -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
                    <i class="fas fa-th-large text-indigo-600 mr-2"></i>Manage These Categories
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div class="bg-green-50 p-4 rounded-lg text-center">
                        <i class="fas fa-paw text-green-600 text-2xl mb-2"></i>
                        <p class="font-semibold text-gray-800">Animals</p>
                        <p class="text-xs text-gray-600">11 types</p>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg text-center">
                        <i class="fas fa-cogs text-purple-600 text-2xl mb-2"></i>
                        <p class="font-semibold text-gray-800">Machineries</p>
                        <p class="text-xs text-gray-600">With status</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg text-center">
                        <i class="fas fa-tree text-green-600 text-2xl mb-2"></i>
                        <p class="font-semibold text-gray-800">Trees</p>
                        <p class="text-xs text-gray-600">Multiple types</p>
                    </div>
                    <div class="bg-amber-50 p-4 rounded-lg text-center">
                        <i class="fas fa-tasks text-amber-600 text-2xl mb-2"></i>
                        <p class="font-semibold text-gray-800">Projects</p>
                        <p class="text-xs text-gray-600">Track progress</p>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-lg text-center">
                        <i class="fas fa-building text-blue-600 text-2xl mb-2"></i>
                        <p class="font-semibold text-gray-800">Infrastructure</p>
                        <p class="text-xs text-gray-600">Assets</p>
                    </div>
                    <div class="bg-indigo-50 p-4 rounded-lg text-center">
                        <i class="fas fa-file-alt text-indigo-600 text-2xl mb-2"></i>
                        <p class="font-semibold text-gray-800">Documents</p>
                        <p class="text-xs text-gray-600">PDF storage</p>
                    </div>
                </div>
            </div>

            <!-- Documentation -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl shadow-lg p-8 text-white mb-12">
                <h2 class="text-2xl font-bold mb-6 text-center">
                    <i class="fas fa-book mr-2"></i>Documentation
                </h2>
                <div class="grid md:grid-cols-4 gap-4">
                    <a href="QUICK_START.md" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-4 text-center transition">
                        <i class="fas fa-rocket text-3xl mb-2"></i>
                        <p class="font-semibold">Quick Start</p>
                        <p class="text-xs opacity-90">Installation guide</p>
                    </a>
                    <a href="README.md" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-4 text-center transition">
                        <i class="fas fa-book-open text-3xl mb-2"></i>
                        <p class="font-semibold">Full Docs</p>
                        <p class="text-xs opacity-90">Complete reference</p>
                    </a>
                    <a href="ARCHITECTURE.md" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-4 text-center transition">
                        <i class="fas fa-project-diagram text-3xl mb-2"></i>
                        <p class="font-semibold">Architecture</p>
                        <p class="text-xs opacity-90">System design</p>
                    </a>
                    <a href="PROJECT_SUMMARY.md" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-4 text-center transition">
                        <i class="fas fa-list-check text-3xl mb-2"></i>
                        <p class="font-semibold">Summary</p>
                        <p class="text-xs opacity-90">Overview</p>
                    </a>
                </div>
            </div>

            <!-- Installation Status -->
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-6 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl mr-4 mt-1"></i>
                    <div>
                        <h3 class="font-bold text-gray-800 mb-2">Installation Required</h3>
                        <p class="text-gray-700 mb-3">Before using the system, please complete these steps:</p>
                        <ol class="list-decimal list-inside space-y-2 text-gray-700">
                            <li>Create database: <code class="bg-gray-200 px-2 py-1 rounded">farm_management</code></li>
                            <li>Import <code class="bg-gray-200 px-2 py-1 rounded">database_setup.sql</code></li>
                            <li>Configure <code class="bg-gray-200 px-2 py-1 rounded">config/database.php</code></li>
                            <li>Access dashboard or admin panel</li>
                        </ol>
                        <p class="mt-3 text-sm">
                            <i class="fas fa-arrow-right mr-2"></i>
                            <a href="QUICK_START.md" class="text-blue-600 hover:underline font-semibold">Read Quick Start Guide</a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-12 text-gray-600">
                <p class="mb-2">
                    <i class="fas fa-code mr-2"></i>
                    Built with PHP, MySQL, Tailwind CSS, and Chart.js
                </p>
                <p class="text-sm">
                    <i class="fas fa-shield-alt mr-2 text-green-600"></i>
                    Secure CRUD with PDO prepared statements
                </p>
            </div>
        </div>
    </div>
</body>
</html>