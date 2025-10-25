// app.js - JavaScriptファイル（完全版）

// 翻訳データ
const translations = window.translationsData || {};

let currentLang = 'en';

// 言語切り替え
function switchLanguage(lang) {
    currentLang = lang;
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (translations[lang] && translations[lang][key]) {
            el.textContent = translations[lang][key];
        }
    });
    document.getElementById('langLabel').textContent = lang === 'en' ? 'JP' : 'EN';

    // Format budget period date
    const budgetPeriodEl = document.getElementById('budgetPeriod');
    if (budgetPeriodEl) {
        const year = budgetPeriodEl.getAttribute('data-year');
        const month = budgetPeriodEl.getAttribute('data-month');
        if (lang === 'ja') {
            budgetPeriodEl.textContent = year + '年' + month + '月';
        } else {
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                              'July', 'August', 'September', 'October', 'November', 'December'];
            budgetPeriodEl.textContent = monthNames[parseInt(month) - 1] + ' ' + year;
        }
    }
}

// ページ読み込み時
window.addEventListener('load', () => {
    switchLanguage('en');
});

// 言語切り替えボタン
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('langToggle').addEventListener('click', () => {
        switchLanguage(currentLang === 'en' ? 'ja' : 'en');
    });
});

// Highcharts テーマ設定
const lightTheme = {
    chart: {
        backgroundColor: 'transparent',
        style: { color: '#212529' }
    },
    title: { style: { color: '#212529' } },
    subtitle: { style: { color: '#212529' } },
    xAxis: {
        labels: { style: { color: '#212529' } },
        title: { style: { color: '#212529' } }
    },
    yAxis: {
        labels: { style: { color: '#212529' } },
        title: { style: { color: '#212529' } }
    },
    legend: { itemStyle: { color: '#212529' } },
    plotOptions: {
        series: { dataLabels: { style: { color: '#212529' } } },
        pie: { dataLabels: { style: { color: '#212529' } } },
        bar: { dataLabels: { style: { color: '#212529' } } },
        line: { dataLabels: { style: { color: '#212529' } } },
        column: { dataLabels: { style: { color: '#212529' } } }
    }
};

const darkTheme = {
    chart: {
        backgroundColor: 'transparent',
        style: { color: '#f8f9fa' }
    },
    title: { style: { color: '#f8f9fa' } },
    subtitle: { style: { color: '#f8f9fa' } },
    xAxis: {
        labels: { style: { color: '#f8f9fa' } },
        title: { style: { color: '#f8f9fa' } }
    },
    yAxis: {
        labels: { style: { color: '#f8f9fa' } },
        title: { style: { color: '#f8f9fa' } }
    },
    legend: { itemStyle: { color: '#f8f9fa' } },
    plotOptions: {
        series: { dataLabels: { style: { color: '#f8f9fa' } } },
        pie: { dataLabels: { style: { color: '#f8f9fa' } } },
        bar: { dataLabels: { style: { color: '#f8f9fa' } } },
        line: { dataLabels: { style: { color: '#f8f9fa' } } },
        column: { dataLabels: { style: { color: '#f8f9fa' } } }
    }
};

// Highcharts 初期設定
Highcharts.setOptions({
    credits: { enabled: false },
    lang: { other: 'unification etc.' }
});

const chartColors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
    '#FF9F40', '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A',
    '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2', '#F8B739',
    '#52B788', '#E76F51', '#2A9D8F', '#E9C46A', '#F4A261'
];

const html = document.documentElement;

// ショップ別グラフ描画
function renderShopChart(shop_data_above_4pct, others_shop, shop_data_below_4pct_total) {
    let shopChartData = [];

    shop_data_above_4pct.forEach((d, i) => {
        shopChartData.push({
            name: d.label1,
            y: parseFloat(d.total),
            color: chartColors[i % chartColors.length]
        });
    });

    if (shop_data_below_4pct_total > 0) {
        shopChartData.push({
            name: 'unification Others',
            y: shop_data_below_4pct_total,
            color: '#CCCCCC'
        });
    }

    if (others_shop && parseFloat(others_shop.total) > 0) {
        shopChartData.push({
            name: others_shop.label1,
            y: parseFloat(others_shop.total),
            color: '#999999'
        });
    }

    const isMobile = window.innerWidth <= 768;
    const labelFormat = isMobile ? '{point.percentage:.1f}%' : '<b>{point.name}</b>: {point.percentage:.1f}%';

    Highcharts.chart('shopChart', {
        chart: { type: 'pie' },
        title: { text: '' },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: labelFormat,
                    distance: isMobile ? -30 : 5
                }
            }
        },
        legend: { enabled: false },
        series: [{
            name: 'Amount',
            data: shopChartData
        }]
    });
}

// カテゴリ別グラフ描画
function renderCategoryChart(categoryData) {
    Highcharts.chart('categoryChart', {
        chart: { type: 'bar' },
        title: { text: '' },
        xAxis: {
            categories: categoryData.map(d => d.label2),
            title: { text: '' }
        },
        yAxis: {
            title: { text: '/ 1,000' },
            labels: {
                formatter: function() {
                    return (this.value / 1000).toLocaleString('en-US', {
                        maximumFractionDigits: 0
                    });
                }
            }
        },
        legend: { enabled: false },
        plotOptions: {
            bar: { dataLabels: { enabled: false } }
        },
        series: [{
            name: 'Amount',
            data: categoryData.map(d => {
                const value = parseFloat(d.total);
                return {
                    y: value,
                    color: value < 0 ? '#36A2EB' : '#D63384'
                };
            })
        }]
    });
}

// 日別推移グラフ描画
function renderDailyChart(dailyData) {
    Highcharts.chart('dailyChart', {
        chart: { type: 'line' },
        title: { text: '' },
        xAxis: {
            categories: dailyData.map(d => {
                const date = new Date(d.re_date);
                const yy = String(date.getFullYear()).slice(-2);
                const mm = String(date.getMonth() + 1).padStart(2, '0');
                const dd = String(date.getDate()).padStart(2, '0');
                return yy + mm + dd;
            }),
            title: { text: '' }
        },
        yAxis: {
            title: { text: '/ 1,000' },
            labels: {
                formatter: function() {
                    return (this.value / 1000).toLocaleString('en-US', {
                        maximumFractionDigits: 0
                    });
                }
            }
        },
        legend: { enabled: false },
        series: [{
            name: 'Daily Expense',
            data: dailyData.map(d => parseFloat(d.daily_total)),
            color: '#FF6384'
        }]
    });
}

// 日別累積推移グラフ描画
function renderCumulativeChart(dailyData) {
    let cumulative = 0;
    const cumulativeData = dailyData.map(d => {
        cumulative += parseFloat(d.daily_total);
        return cumulative;
    });

    Highcharts.chart('cumulativeChart', {
        chart: { type: 'line' },
        title: { text: '' },
        xAxis: {
            categories: dailyData.map(d => {
                const date = new Date(d.re_date);
                const yy = String(date.getFullYear()).slice(-2);
                const mm = String(date.getMonth() + 1).padStart(2, '0');
                const dd = String(date.getDate()).padStart(2, '0');
                return yy + mm + dd;
            }),
            title: { text: '' }
        },
        yAxis: {
            title: { text: '/ 1,000' },
            labels: {
                formatter: function() {
                    return (this.value / 1000).toLocaleString('en-US', {
                        maximumFractionDigits: 0
                    });
                }
            }
        },
        legend: { enabled: false },
        series: [{
            name: 'Cumulative Expense',
            data: cumulativeData,
            color: '#36A2EB'
        }]
    });
}

// 期間別推移グラフ描画
function renderPeriodChart(periodData, periodRange) {
    const isMonthly = periodRange < 60;

    document.getElementById('periodTrendLabel').textContent = isMonthly ? 'Monthly Expense Trend' :
        'Annual Expense Trend';

    const periods = [...new Set(periodData.map(d => d.period))];
    const shops = [...new Set(periodData.map(d => d.shop_name))];

    const periodSeries = shops.map((shop, index) => {
        const data = periods.map(period => {
            const item = periodData.find(d => d.period === period && d.shop_name === shop);
            return item ? parseFloat(item.total) : 0;
        });

        return {
            name: shop,
            data: data,
            color: chartColors[index % chartColors.length]
        };
    });

    Highcharts.chart('periodChart', {
        chart: { type: 'column' },
        title: { text: '' },
        xAxis: {
            categories: periods,
            title: { text: '' }
        },
        yAxis: {
            title: { text: '/ 1,000' },
            labels: {
                formatter: function() {
                    return (this.value / 1000).toLocaleString('en-US', {
                        maximumFractionDigits: 0
                    });
                }
            },
            stacking: 'normal'
        },
        legend: { enabled: false },
        plotOptions: {
            column: { stacking: 'normal' }
        },
        series: periodSeries
    });
}

// テーマ適用してすべてのグラフを再描画
function applyHighchartsTheme(chartData) {
    const isDark = html.getAttribute('data-bs-theme') === 'dark';
    const theme = isDark ? darkTheme : lightTheme;
    Highcharts.setOptions(theme);

    renderShopChart(chartData.shop_data_above_4pct, chartData.others_shop, chartData.shop_data_below_4pct_total);
    renderCategoryChart(chartData.category_data);
    renderDailyChart(chartData.daily_data);
    renderCumulativeChart(chartData.daily_data);
    renderPeriodChart(chartData.period_data, chartData.period_range);
}

// テーマ切り替え
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('themeToggle').addEventListener('click', () => {
        const newTheme = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', newTheme);
        document.getElementById('themeIcon').className = newTheme === 'dark' ? 'bi bi-moon-fill' :
            'bi bi-sun-fill';
    });
});

// リセットボタン
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('resetBtn').addEventListener('click', () => {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        document.getElementById('startDate').value = year + '-' + month + '-01';
        document.getElementById('endDate').value = year + '-' + month + '-' + day;
    });
});

// ショップで検索
function searchByShop(shop) {
    const params = new URLSearchParams(window.location.search);
    params.set('search_shop', shop);
    if (!params.has('start_date')) params.set('start_date', window.startDate);
    if (!params.has('end_date')) params.set('end_date', window.endDate);
    if (!params.has('recent_limit')) params.set('recent_limit', window.recentLimit);
    window.location.href = '?' + params.toString() + '#recentTransactionsSection';
}

// カテゴリで検索
function searchByCategory(category) {
    const params = new URLSearchParams(window.location.search);
    params.set('search_category', category);
    if (!params.has('start_date')) params.set('start_date', window.startDate);
    if (!params.has('end_date')) params.set('end_date', window.endDate);
    if (!params.has('recent_limit')) params.set('recent_limit', window.recentLimit);
    window.location.href = '?' + params.toString() + '#recentTransactionsSection';
}

// フィルター削除
function removeFilter(filterType) {
    const params = new URLSearchParams(window.location.search);
    if (filterType === 'shop') params.delete('search_shop');
    else if (filterType === 'category') params.delete('search_category');
    if (!params.has('start_date')) params.set('start_date', window.startDate);
    if (!params.has('end_date')) params.set('end_date', window.endDate);
    if (!params.has('recent_limit')) params.set('recent_limit', window.recentLimit);
    window.location.href = '?' + params.toString() + '#recentTransactionsSection';
}

// 検索クリア
function clearSearch() {
    const params = new URLSearchParams();
    params.set('start_date', window.startDate);
    params.set('end_date', window.endDate);
    params.set('recent_limit', window.recentLimit);
    window.location.href = '?' + params.toString() + '#recentTransactionsSection';
}

// マスター管理機能
function showAddShopDialog() {
    const shopName = prompt('新しいショップ名を入力してください:');
    if (shopName && shopName.trim()) {
        addMasterData('shop', shopName.trim());
    }
}

function showAddCategoryDialog() {
    const categoryName = prompt('新しいカテゴリ名を入力してください:');
    if (categoryName && categoryName.trim()) {
        addMasterData('category', categoryName.trim());
    }
}

function addMasterData(type, name) {
    const data = new FormData();
    data.append('action', type === 'shop' ? 'add_shop' : 'add_category');
    data.append('name', name);

    // CSRFトークンを追加
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    data.append('csrf_token', csrfToken);

    fetch(window.location.href, {
            method: 'POST',
            body: data
        })
        .then(response => response.text())
        .then(result => {
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('エラーが発生しました');
        });
}

// タブナビゲーション処理
window.addEventListener('load', function() {
    if (window.activeTab === 'entry') {
        const entryTab = new bootstrap.Tab(document.getElementById('entry-tab'));
        entryTab.show();
    }
});

// トランザクション編集
function editTransaction(id, date, price, shop, category) {
    // モーダルにデータをセット
    document.getElementById('edit_transaction_id').value = id;
    document.getElementById('edit_re_date').value = date;
    document.getElementById('edit_price').value = price;
    document.getElementById('edit_label1').value = shop;
    document.getElementById('edit_label2').value = category;

    // モーダルを表示
    const modal = new bootstrap.Modal(document.getElementById('editTransactionModal'));
    modal.show();
}

// トランザクション削除
function deleteTransaction(id) {
    const lang = currentLang;
    const confirmMsg = translations[lang]['confirmDelete'] || 'Are you sure you want to delete this transaction?';

    if (confirm(confirmMsg)) {
        // CSRFトークンを取得
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // フォームを作成して送信
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = csrfToken;

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_transaction';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;

        form.appendChild(csrfInput);
        form.appendChild(actionInput);
        form.appendChild(idInput);

        document.body.appendChild(form);
        form.submit();
    }
}