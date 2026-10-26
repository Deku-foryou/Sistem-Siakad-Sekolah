@section('title', 'Dashboard')

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
                <div class="mb-3 mb-lg-0">
                    <p class="p-0 m-0 h3 fw-bold">Dashboard</p>
                    <div class="mt-2" style="color: #868686">
                        Your main content goes here..
                    </div>
                </div>
            </div>
        </div>

        <!-- Top 4 Cards -->
        <div class="row mb-4">
            <!-- Total Sales -->
            <div class="col-lg-3 mb-3">
                <div class="card p-4 border-0 h-100" style="border-radius: 12px; background-color: #fff0eb; border: 1px solid #fcd4c7 !important;">
                    <div class="d-flex align-items-start gap-3">
                        <div class="d-flex justify-content-center align-items-center rounded" style="width: 45px; height: 45px; background-color: #f15833;">
                            <i class="fa-solid fa-clipboard-list text-white"></i>
                        </div>
                        <div>
                            <div class="text-secondary fw-medium" style="font-size: 14px;">Total Sales</div>
                            <div class="h3 mb-1 fw-bold mt-2">$25,000</div>
                            <div style="color: #f15833; font-size: 12px; font-weight: 600;">+5% since last month</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Total Purchase -->
            <div class="col-lg-3 mb-3">
                <div class="card p-4 border-0 h-100" style="border-radius: 12px; background-color: #ebfaef; border: 1px solid #c2eecf !important;">
                    <div class="d-flex align-items-start gap-3">
                        <div class="d-flex justify-content-center align-items-center rounded" style="width: 45px; height: 45px; background-color: #10b981;">
                            <i class="fa-solid fa-repeat text-white"></i>
                        </div>
                        <div>
                            <div class="text-secondary fw-medium" style="font-size: 14px;">Total Purchase</div>
                            <div class="h3 mb-1 fw-bold mt-2">$18,000</div>
                            <div style="color: #10b981; font-size: 12px; font-weight: 600;">+22% since last month</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Total Expenses -->
            <div class="col-lg-3 mb-3">
                <div class="card p-4 border-0 h-100" style="border-radius: 12px; background-color: #eaf8fb; border: 1px solid #bcecf5 !important;">
                    <div class="d-flex align-items-start gap-3">
                        <div class="d-flex justify-content-center align-items-center rounded" style="width: 45px; height: 45px; background-color: #06b6d4;">
                            <i class="fa-solid fa-dollar-sign text-white"></i>
                        </div>
                        <div>
                            <div class="text-secondary fw-medium" style="font-size: 14px;">Total Expenses</div>
                            <div class="h3 mb-1 fw-bold mt-2">$9,000</div>
                            <div style="color: #06b6d4; font-size: 12px; font-weight: 600;">+10% since last month</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Invoice Due -->
            <div class="col-lg-3 mb-3">
                <div class="card p-4 border-0 h-100" style="border-radius: 12px; background-color: #fff9eb; border: 1px solid #fcebb5 !important;">
                    <div class="d-flex align-items-start gap-3">
                        <div class="d-flex justify-content-center align-items-center rounded" style="width: 45px; height: 45px; background-color: #f59e0b;">
                            <i class="fa-solid fa-file-invoice text-white"></i>
                        </div>
                        <div>
                            <div class="text-secondary fw-medium" style="font-size: 14px;">Invoice Due</div>
                            <div class="h3 mb-1 fw-bold mt-2">$25,000</div>
                            <div style="color: #f59e0b; font-size: 12px; font-weight: 600;">+35% since last month</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3 Cards Second Row -->
        <div class="row mb-4">
            <!-- Total Profit -->
            <div class="col-lg-4 mb-3">
                <div class="card p-4 h-100 shadow-sm border-0" style="border-radius: 12px;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="h3 mb-1 fw-bold">$25,458</div>
                            <div class="text-secondary fw-medium" style="font-size: 14px;">Total Profit</div>
                        </div>
                        <div class="text-danger">
                            <i class="fa-regular fa-clone fa-lg"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-2">
                        <div style="color: #10b981; font-size: 12px; font-weight: 600;">+35% vs Last Month</div>
                        <a href="#" class="text-danger text-decoration-underline" style="font-size: 13px; font-weight: 600;">View</a>
                    </div>
                </div>
            </div>
            
            <!-- Total Payment Returns -->
            <div class="col-lg-4 mb-3">
                <div class="card p-4 h-100 shadow-sm border-0" style="border-radius: 12px;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="h3 mb-1 fw-bold">$45,458</div>
                            <div class="text-secondary fw-medium" style="font-size: 14px;">Total Payment Returns</div>
                        </div>
                        <div class="text-danger">
                            <i class="fa-regular fa-credit-card fa-lg"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-2">
                        <div style="color: #f15833; font-size: 12px; font-weight: 600;">-20% vs Last Month</div>
                        <a href="#" class="text-danger text-decoration-underline" style="font-size: 13px; font-weight: 600;">View</a>
                    </div>
                </div>
            </div>
            
            <!-- Total Expenses (3rd card) -->
            <div class="col-lg-4 mb-3">
                <div class="card p-4 h-100 shadow-sm border-0" style="border-radius: 12px;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="h3 mb-1 fw-bold">$34,458</div>
                            <div class="text-secondary fw-medium" style="font-size: 14px;">Total Expenses</div>
                        </div>
                        <div class="text-warning">
                            <i class="fa-solid fa-money-bill-wave fa-lg"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-2">
                        <div style="color: #f59e0b; font-size: 12px; font-weight: 600;">-20% vs Last Month</div>
                        <a href="#" class="text-danger text-decoration-underline" style="font-size: 13px; font-weight: 600;">View</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <!-- Bar Chart -->
            <div class="col-lg-7 mb-3">
                <div class="card p-4 shadow-sm border-0 h-100" style="border-radius: 12px;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold m-0" style="font-size: 16px;">Sales vs Purchase</h5>
                        <select class="form-select form-select-sm" style="width: auto; border: 1px solid #eee; border-radius: 8px;">
                            <option>This Year</option>
                        </select>
                    </div>
                    <div style="height: 300px;">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Overall Information -->
            <div class="col-lg-5 mb-3">
                <div class="card p-4 shadow-sm border-0 h-100" style="border-radius: 12px;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold m-0" style="font-size: 16px;">Overall Information</h5>
                        <select class="form-select form-select-sm" style="width: auto; border: 1px solid #eee; border-radius: 8px;">
                            <option>Last 6 Months</option>
                        </select>
                    </div>
                    <div class="text-secondary fw-medium mb-3" style="font-size: 14px;">Customers Overview</div>
                    
                    <div class="row align-items-center mb-4 mt-2">
                        <div class="col-5">
                            <div style="height: 140px;">
                                <canvas id="donutChart"></canvas>
                            </div>
                        </div>
                        <div class="col-7">
                            <div class="d-flex justify-content-around">
                                <div class="text-center">
                                    <div class="h4 fw-bold mb-1">5.5K</div>
                                    <div class="text-success fw-medium mb-1" style="font-size: 13px;">First Time</div>
                                    <span class="badge bg-success rounded-pill px-3 py-1 mt-1" style="font-size: 11px;">↑ 25%</span>
                                </div>
                                <div class="text-center">
                                    <div class="h4 fw-bold mb-1">3.5K</div>
                                    <div class="text-warning fw-medium mb-1" style="font-size: 13px;">Return</div>
                                    <span class="badge bg-success rounded-pill px-3 py-1 mt-1" style="font-size: 11px;">↑ 21%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-auto pt-4 pb-2 border-top">
                        <div class="text-center">
                            <div class="h4 fw-bold mb-0">6987</div>
                        </div>
                        <div class="text-center">
                            <div class="h4 fw-bold mb-0">4896</div>
                        </div>
                        <div class="text-center">
                            <div class="h4 fw-bold mb-0">487</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('livewire:navigated', () => {
    initCharts();
});

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
});

function initCharts() {
    // Check if element exists before initializing
    if (document.getElementById('salesChart')) {
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['28 Jan', '29 Jan', '30 Jan', '31 Jan', '1 Feb', '2 Feb', '3 Feb', '4 Feb', '5 Feb'],
                datasets: [{
                    label: 'Sales',
                    data: [75, 85, 100, 98, 88, 105, 90, 115, 95],
                    backgroundColor: '#f15833',
                    borderRadius: 4,
                    barPercentage: 0.8,
                    categoryPercentage: 0.4
                }, {
                    label: 'Purchase',
                    data: [55, 58, 60, 62, 58, 65, 55, 68, 58],
                    backgroundColor: '#f9a88f',
                    borderRadius: 4,
                    barPercentage: 0.8,
                    categoryPercentage: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 120,
                        ticks: { callback: function(value) { return value + 'k'; } },
                        grid: { borderDash: [5, 5], color: '#f0f0f0' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    if (document.getElementById('donutChart')) {
        const donutCtx = document.getElementById('donutChart').getContext('2d');
        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: ['Return', 'First Time'],
                datasets: [{
                    data: [35, 65],
                    backgroundColor: ['#f59e0b', '#10b981'],
                    borderWidth: 0,
                    cutout: '75%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
}
</script>