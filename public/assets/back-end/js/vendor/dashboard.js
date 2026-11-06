"use strict";
let isMonthCheckedForEarningStatistic = false;
let countLabel = 0;
let currencySymbol = $('#get-currency-symbol').data('currency-symbol')

$(document).ready(function () {
    $("#statistics_type").on("change", function () {
        let type = $(this).val();
        let url = $('#order-status-url').data('url');
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.get({
            url: url.replace(':type', type),
            success: function (data) {
                $('#order_stats').html(data.view)
            },
        });
    });
});

function earningStatistics(){
    $('.earn-statistics').on('click', function () {
        let value = $(this).attr('data-date-type');
        let url = $('#earn-statistics').data('action');
        $.ajax({
            url: url,
            type: 'GET',
            data: {
                type: value
            },
            beforeSend: function () {
                $('#loading').fadeIn();
            },
            success: function (data) {
                $('#earn-statistics-div').empty().html(data.view);
                setMonthResponsiveDataForEarningStatistic();
                countLabel = parseInt($('input[name=earn_statistics_label_count]').val());
                earningStatisticsApexChart();
                earningStatistics();
            },
            complete: function () {
                $('#loading').fadeOut();
            }
        });
    });
}
earningStatistics();
function setMonthResponsiveDataForEarningStatistic() {
    $('.earn-statistics-option input:radio[name="statistics"]').change(function() {
        isMonthCheckedForEarningStatistic = $('input:radio[name="statistics"][value="MonthEarn"]').is(':checked');
    });
}
setMonthResponsiveDataForEarningStatistic();
let windowSize = getWindowSize();

function earningStatisticsApexChart(){
    let earnStatisticsData = $('#earn-statistics-data');
    const vendorEarn = earnStatisticsData.data('vendor-earn');
    const commissionEarn = earnStatisticsData.data('commission-earn');
    let label = earnStatisticsData.data('label');
    if (windowSize.width < 767){
        label =  getLabelData(label,countLabel,isMonthCheckedForEarningStatistic)
    }
    var options = {
        series: [
            {
                name: earnStatisticsData.data('vendor-text'),
                data: Object.values(vendorEarn)
            },
            {
                name: earnStatisticsData.data('commission-text'),
                data: Object.values(commissionEarn)
            }
        ],
        chart: {
            height: 386,
            type: 'line',
            dropShadow: {
                enabled: true,
                color: '#000',
                top: 18,
                left: 7,
                blur: 10,
                opacity: 0.2
            },
            toolbar: {
                show: false
            }
        },
        yaxis: {
            labels: {
                offsetX: 0,
                formatter: function(value) {
                    return  currencySymbol+value
                }
            },
        },
        colors: ['#4FA7FF', '#82C662'],
        dataLabels: {
            enabled: false,
        },
        stroke: {
            curve: 'smooth',
        },
        grid: {
            xaxis: {
                lines: {
                    show: true
                }
            },
            yaxis: {
                lines: {
                    show: true
                }
            },
            borderColor: '#CAD2FF',
            strokeDashArray: 5,
        },
        markers: {
            size: 1
        },
        theme: {
            mode: 'light',
        },
        xaxis: {
            categories: Object.values(label)
        },
        legend: {
            position: 'top',
            horizontalAlign: 'center',
            floating: false,
            offsetY: -10,
            offsetX: 0,
            itemMargin: {
                horizontal: 10,
                vertical: 10
            },
        },
        padding: {
            top: 0,
            right: 0,
            bottom: 200,
            left: 10
        },
    };
    var chart = new ApexCharts(document.getElementById("earning-apex-line-chart"), options);
    chart.render();
}
earningStatisticsApexChart();

function getLabelData(label,count,status){
    let mod = (count % 5);
    if (status === true ) {
        label.forEach((val, index) => {
            if (val % 5 === 0 || val === count) {
                label[index] = (val !== count && mod <= 1 && (count - mod === val) ? '' : val);
            }else{
                label[index]='';
            }
        });
    }else {
        label.forEach((val, index) => {
            label[index]=val.substring(0,3);
        });
    }
    return label;
}
