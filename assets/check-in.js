import 'fork-awesome/css/fork-awesome.min.css';
import './style/check-in.less';

function updateStats(statistics) {
    $('#checked-in-count').text(statistics.checkedInWithReservations + statistics.checkedInWithoutReservations);
    $('#reserved-count').text(statistics.reservations);
    $('#not-checked-in-count').text(statistics.reservations - statistics.checkedInWithReservations);
    $('#checked-in-without-reservation-count').text(statistics.checkedInWithoutReservations);
}

let statsRequest;
function fetchStats() {
    const route = 'event_stats';
    const statsUrl = Routing.generate(route, { eventId: 1 }, true);
    statsRequest?.abort();
    statsRequest = $.get(statsUrl)
        .done(updateStats)
        .fail(() => console.error('Failed to fetch stats'));
}

// Create a template for the user row
const userRowTemplate = (user) => `
    <div class="table-row ${user.checkedIn ? 'checked-in' : ''}">
        <div>${user.firstname}</div>
        <div>${user.fullLastname}</div>
        <div>${user.divisionName}</div>
        <div>
            <i class="fa fa-fw ${user.reserved ? 'fa-check' : 'fa-times'}" style="color:${user.reserved ? 'green' : 'red'}"></i>
        </div>
        <div>
            <a href="#" class="${user.checkedIn ? 'btn-check-out' : 'btn-check-in'}" data-id="${user.id}" style="${user.checkedIn ? 'opacity: 0.5' : ''}">
                <i class="fa fa-fw ${user.checkedIn ? 'fa-minus' : 'fa-plus'}"></i>
                ${user.checkedIn ? 'Uitchecken' : 'Inchecken'}
            </a>
        </div>
    </div>
`;

function populateTableBody(users) {
    const tableBody = $('.table-body');
    tableBody.empty(); // Clear existing rows
    users.forEach(user => {
        tableBody.append(userRowTemplate(user));
    });
    initializeEventHandlers();
}

function refreshTableBody(users) {
    fetchStats();
    populateTableBody(users);
}

function handleCheckInOut(action, userId) {
    const route = action === 'check-in' ? 'event_check_in' : 'event_check_out';
    const actionUrl = Routing.generate(route, { eventId: 1 }, true);
    $.post(actionUrl, { userId: userId })
        .done(() => {
            const user = userData.find(u => u.id == userId);
            user.checkedIn = action === 'check-in';
            refreshTableBody(userData);
        })
        .fail(() => console.error(`Failed to ${action} user`));
}

function initializeEventHandlers() {
    $('.btn-check-in').on('click', function (e) {
        e.preventDefault();
        handleCheckInOut('check-in', $(this).data('id'));
    });

    $('.btn-check-out').on('click', function (e) {
        e.preventDefault();
        handleCheckInOut('check-out', $(this).data('id'));
    });
}

const pageTemplate = (num) => `
    <div data-page="${num}" class="pageNum${num === currentPage ? ' active' : ''}">${num}</div>
`;

function updatePagination() {
    const paginationIndicator = $('.pagination-indicator');
    paginationIndicator.empty();
    if (totalPages === 1) return;
    for (let index = 1; index <= totalPages; index++) {
        paginationIndicator.append(pageTemplate(index));
    }
    initializePaginationHandlers();
}

function initializePaginationHandlers() {
    $('.pageNum').on('click', function (e) {
        e.preventDefault();
        const selectedPage = $(this).data('page');
        if (currentPage === selectedPage) return; // skip fetching if it is the same page
        currentPage = selectedPage;
        fetchMembers();
    });
}

let ascendingColumns = [];
let descendingColumns = [];
let searchQuery = "";
let searchRequest;
let currentPage = 1;
let userData = [];
let totalCount = 0;
let totalPages = 1;

function fetchMembers() {
    const route = 'event_attendees';
    const membersUrl = Routing.generate(route, { eventId: 1 }, true);
    searchRequest?.abort();
    searchRequest = $.get(membersUrl, {
        ascending: ascendingColumns,
        descending: descendingColumns,
        search: searchQuery,
        page: currentPage
    })
    .done((response) => {
        userData = response.results;
        totalCount = response.amount;
        totalPages = response.pages;

        if (currentPage > totalPages) {            
            currentPage = 0; // Reset to the first page if out of bounds
            fetchMembers();
            return;
        }

        refreshTableBody(userData);
        updatePagination();
    })
    .fail(() => console.error('Failed to fetch members'));
}

$(document).ready(function () {
    $('#search-input').on('input', function() {
        searchQuery = $(this).val();
        fetchMembers();
    });

    $('#clear-search-button').on('click', function() {
        $('#search-input').val('');
        searchQuery = '';
        fetchMembers();
    });

    $('.table-header .header-cell').on('click', function (e) {
        const column = $(this).data("column");
        const ascIndex = ascendingColumns.indexOf(column);
        const descIndex = descendingColumns.indexOf(column);
        
        if (ascIndex !== -1) {            
            ascendingColumns.splice(ascIndex, 1);
            descendingColumns.push(column);
            $(this).find('i').attr('class', 'fa fa-sort-down');
        } else if (descIndex !== -1) {
            descendingColumns.splice(descIndex, 1);
            $(this).find('i').attr('class', 'fa fa-sort');
        } else {
            ascendingColumns.push(column);
            $(this).find('i').attr('class', 'fa fa-sort-up');
        }
        fetchMembers();
    });

    fetchMembers(); // Initial fetch of members
});
