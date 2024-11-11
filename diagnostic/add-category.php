<?php 
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php'); 
include('./constant/connect.php'); 

// Giả sử bạn lưu danh sách group_id đã chọn trước đó trong cơ sở dữ liệu
$selectedGroupIds = [];
$categoriesId = isset($_GET['id']) ? $_GET['id'] : null;
if ($categoriesId) {
    $selectedGroupSql = "SELECT group_id FROM categories_groups WHERE categories_id = $categoriesId";
    $selectedGroupResult = $connect->query($selectedGroupSql);
    while ($row = $selectedGroupResult->fetch_assoc()) {
        $selectedGroupIds[] = $row['group_id'];
    }
}

// Chuẩn bị JSON để sử dụng trong JavaScript
$selectedGroupIdsJson = json_encode($selectedGroupIds);
?>

<style>
    /* Style cho khung autocomplete */
    .autocomplete-suggestions {
        position: absolute;
        border: 1px solid #ddd;
        background: #fff;
        max-height: 150px;
        overflow-y: auto;
        width: calc(100% - 20px);
        z-index: 1000;
        padding: 5px;
        margin-top: 2px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        display: none;
    }

    .autocomplete-suggestion {
        padding: 8px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }

    .autocomplete-suggestion:hover {
        background-color: #f0f0f0;
    }

    /* Style cho các mục đã chọn */
    .selected-items {
        display: flex;
        flex-wrap: wrap;
        margin-top: 10px;
        opacity: 0; /* Ẩn thanh selected items khi mới tải trang */
        transition: opacity 0.5s ease;
    }

    .selected-item {
        background-color: #007bff;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        margin: 2px;
        display: flex;
        align-items: center;
    }

    .selected-item .remove-item {
        margin-left: 8px;
        cursor: pointer;
        color: #fff;
        font-weight: bold;
    }

    /* Positioning relative for autocomplete */
    .position-relative {
        position: relative;
    }
</style>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary">Add New Test Category</h3> 
        </div>
        <div class="col-md-7 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active">Add Test Category</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8" style="margin-left: 10%;">
                <div class="card">
                    <div class="card-title"></div>
                    <div id="add-category-messages"></div>
                    <div class="card-body">
                        <form class="form-horizontal" method="POST" id="addCategoryForm" action="php_action/createCategories.php" enctype="multipart/form-data">
                            <div class="form-group">
                                <div class="row">
                                    <label class="col-sm-3 control-label">Category Name</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="categoriesName" name="categoriesName" placeholder="Category Name" required />
                                    </div>
                                </div>
                            </div>

                            <div class="form-group position-relative">
                                <div class="row">
                                    <label class="col-sm-3 control-label">Group Names</label>
                                    <div class="col-sm-9">
                                        <input type="text" id="groupNames" class="form-control" placeholder="Start typing to search groups..." autocomplete="off">
                                        <div class="autocomplete-suggestions" id="autocompleteSuggestions"></div>

                                        <!-- Khu vực hiển thị các mục đã chọn -->
                                        <div class="selected-items" id="selectedItems"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Input ẩn để lưu trữ các group đã chọn -->
                            <input type="hidden" name="groupIds" id="groupIds">

                            <div class="form-group">
                                <div class="row">
                                    <label class="col-sm-3 control-label">Status</label>
                                    <div class="col-sm-9">
                                        <select class="form-control" id="categoriesStatus" name="categoriesStatus">
                                            <option value="1">Available</option>
                                            <option value="2">Not Available</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="create" id="createCategoriesBtn" class="btn btn-primary btn-flat m-b-30 m-t-30">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php'); ?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const groupNamesInput = document.getElementById("groupNames");
        const autocompleteSuggestions = document.getElementById("autocompleteSuggestions");
        const selectedItemsContainer = document.getElementById("selectedItems");
        const groupIdsInput = document.getElementById("groupIds");

        // Hiện selected items sau khi trang tải xong
        window.onload = function() {
            selectedItemsContainer.style.opacity = "1";
        };

        // Khởi tạo nhóm đã chọn từ PHP
        const selectedGroupIds = <?php echo $selectedGroupIdsJson; ?>;
        let selectedGroups = [];

        // Nạp nhóm đã chọn từ DB
        fetchSelectedGroups();

        function fetchSelectedGroups() {
            fetch(`php_action/fetchSelectedGroups.php?groupIds=${selectedGroupIds.join(",")}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(group => addSelectedItem(group.group_id, group.group_name));
                })
                .catch(error => console.error("Error:", error));
        }

        // Hiển thị tất cả gợi ý khi nhấp vào ô nhập
        groupNamesInput.addEventListener("focus", fetchAndDisplaySuggestions);

        // Gửi yêu cầu đến server khi người dùng nhập
        groupNamesInput.addEventListener("input", fetchAndDisplaySuggestions);

        // Đóng gợi ý khi click ra ngoài
        document.addEventListener("click", function(e) {
            if (!groupNamesInput.contains(e.target) && !autocompleteSuggestions.contains(e.target)) {
                autocompleteSuggestions.innerHTML = "";
                autocompleteSuggestions.style.display = "none";
            }
        });

        // Lấy và hiển thị gợi ý
        function fetchAndDisplaySuggestions() {
            const query = groupNamesInput.value;
            fetch(`php_action/fetchGroups.php?searchTerm=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => showSuggestions(data))
                .catch(error => console.error("Error:", error));
        }

        // Hiển thị các gợi ý
        function showSuggestions(groups) {
            autocompleteSuggestions.innerHTML = "";
            autocompleteSuggestions.style.display = "block";

            // Lọc các group chưa chọn
            const filteredGroups = groups.filter(group => !selectedGroups.some(g => g.id == group.group_id));

            filteredGroups.forEach(group => {
                const suggestion = document.createElement("div");
                suggestion.classList.add("autocomplete-suggestion");
                suggestion.textContent = group.group_name;
                suggestion.dataset.id = group.group_id;
                suggestion.addEventListener("click", function() {
                    addSelectedItem(group.group_id, group.group_name);
                });
                autocompleteSuggestions.appendChild(suggestion);
            });
        }

        // Thêm mục đã chọn vào danh sách
        function addSelectedItem(groupId, groupName) {
            if (!selectedGroups.some(group => group.id === groupId)) {
                selectedGroups.push({ id: groupId, name: groupName });
                updateSelectedItemsUI();
                groupNamesInput.value = "";  // Xóa ô nhập sau khi chọn
                autocompleteSuggestions.innerHTML = "";
                autocompleteSuggestions.style.display = "none";
                updateGroupIdsInput();
            }
        }

        // Cập nhật giao diện các mục đã chọn
        function updateSelectedItemsUI() {
            selectedItemsContainer.innerHTML = "";
            selectedGroups.forEach(group => {
                const item = document.createElement("div");
                item.classList.add("selected-item");
                item.textContent = group.name;

                const removeBtn = document.createElement("span");
                removeBtn.classList.add("remove-item");
                removeBtn.textContent = " x";
                removeBtn.addEventListener("click", function() {
                    selectedGroups = selectedGroups.filter(g => g.id !== group.id);
                    updateSelectedItemsUI();
                    updateGroupIdsInput();
                });

                item.appendChild(removeBtn);
                selectedItemsContainer.appendChild(item);
            });
        }

        // Cập nhật giá trị cho input ẩn
        function updateGroupIdsInput() {
            const groupIds = selectedGroups.map(group => group.id);
            groupIdsInput.value = groupIds.join(",");
        }
    });
</script>
