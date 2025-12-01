<!-- VIEW: Animals (Hidden by default) -->
<div id="view-animals" class="hidden space-y-6 animate-fade-in">
    <h2 class="text-2xl font-bold text-gray-800">Animal Roster</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- Animal Card 1 -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
            <img src="https://images.unsplash.com/photo-1552053831-71594a27632d?auto=format&fit=crop&w=400&q=80" alt="Barnaby" class="w-full h-48 object-cover">
            <div class="p-4">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-bold text-gray-800">Barnaby</h3>
                    <span class="text-xs font-semibold bg-gray-100 px-2 py-1 rounded text-gray-600">Dog</span>
                </div>
                <p class="text-gray-500 text-sm mb-4">Golden Retriever</p>
                <button class="w-full py-2 bg-orange-50 text-orange-600 font-medium rounded-lg hover:bg-orange-100 transition-colors">
                    View Profile
                </button>
            </div>
        </div>

        <!-- Animal Card 2 -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
            <img src="https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=400&q=80" alt="Mittens" class="w-full h-48 object-cover">
            <div class="p-4">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-bold text-gray-800">Mittens</h3>
                    <span class="text-xs font-semibold bg-gray-100 px-2 py-1 rounded text-gray-600">Cat</span>
                </div>
                <p class="text-gray-500 text-sm mb-4">Tabby</p>
                <button class="w-full py-2 bg-orange-50 text-orange-600 font-medium rounded-lg hover:bg-orange-100 transition-colors">
                    View Profile
                </button>
            </div>
        </div>

        <!-- Animal Card 3 -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
            <img src="https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?auto=format&fit=crop&w=400&q=80" alt="Rocky" class="w-full h-48 object-cover">
            <div class="p-4">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-bold text-gray-800">Rocky</h3>
                    <span class="text-xs font-semibold bg-gray-100 px-2 py-1 rounded text-gray-600">Dog</span>
                </div>
                <p class="text-gray-500 text-sm mb-4">Bulldog Mix</p>
                <button class="w-full py-2 bg-orange-50 text-orange-600 font-medium rounded-lg hover:bg-orange-100 transition-colors">
                    View Profile
                </button>
            </div>
        </div>

        <!-- Placeholder Card 1 -->
        <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-xl flex flex-col items-center justify-center h-80 hover:bg-gray-100 transition-colors cursor-pointer group">
            <div class="p-4 bg-white rounded-full mb-3 shadow-sm group-hover:scale-110 transition-transform">
                <i data-lucide="plus" class="text-gray-400 w-8 h-8"></i>
            </div>
            <p class="text-gray-400 font-medium group-hover:text-gray-600">Add New Animal</p>
        </div>

    </div>
</div>