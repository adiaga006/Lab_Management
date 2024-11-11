<?php 
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php'); 
include('./constant/connect.php'); 

$categoriesId = $_GET['id'];

// Fetch category information
$sql = "SELECT * FROM categories WHERE categories_id = '$categoriesId'";
$result = $connect->query($sql)->fetch_assoc();

// Fetch groups not linked to the category
$availableGroupsSql = "
    SELECT g.group_id, g.group_name 
    FROM groups g
    WHERE g.group_id NOT IN (
        SELECT cg.group_id 
        FROM category_groups cg 
        WHERE cg.category_id = '$categoriesId'
    )";
$availableGroupsResult = $connect->query($availableGroupsSql);
$availableGroups = [];
while ($row = $availableGroupsResult->fetch_assoc()) {
    $availableGroups[] = ['id' => $row['group_id'], 'name' => $row['group_name']];
}

// Fetch already linked groups
$linkedGroupsSql = "
    SELECT cg.group_id, g.group_name 
    FROM category_groups cg
    JOIN groups g ON cg.group_id = g.group_id 
    WHERE cg.category_id = '$categoriesId'";
$linkedGroupsResult = $connect->query($linkedGroupsSql);
$linkedGroups = [];
while ($row = $linkedGroupsResult->fetch_assoc()) {
    $linkedGroups[] = ['id' => $row['group_id'], 'name' => $row['group_name']];
}
?>

<style>
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

    .selected-items {
        display: flex;
        flex-wrap: wrap;
        margin-top: 10px;
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
</style>

<!-- HTML structure for form with selected and unselected groups -->
<div class="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8" style="margin-left: 10%;">
                <div class="card">
                    <div class="card-body">
                        <form class="form-horizontal" method="POST" id="editCategoryForm" action="php_action/editCategories.php?id=<?php echo $categoriesId; ?>" enctype="multipart/form-data">
                            <div class="form-group">
                                <div class="row">
                                    <label class="col-sm-3 control-label">Category Name</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="categoriesName" name="categoriesName" value="<?php echo $result['categories_name']; ?>" required />
                                    </div>
                                </div>
                            </div>

                            <div class="form-group position-relative">
                                <div class="row">
                                    <label class="col-sm-3 control-label">Group Names</label>
                                    <div class="col-sm-9">
                                        <input type="text" id="groupNames" class="form-control" placeholder="Start typing to search groups..." autocomplete="off">
                                        <div class="autocomplete-suggestions" id="autocompleteSuggestions"></div>

                                        <!-- Display selected groups -->
                                        <div class="selected-items" id="selectedItems">
                                            <?php foreach ($linkedGroups as $group): ?>
                                                <div class='selected-item' data-id='<?php echo $group['id']; ?>'>
                                                    <?php echo $group['name']; ?>
                                                    <span class='remove-item'> x</span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden input to store selected group IDs -->
                            <input type="hidden" name="groupIds" id="groupIds" value="<?php echo implode(',', array_column($linkedGroups, 'id')); ?>">

                            <div class="form-group">
                                <div class="row">
                                    <label class="col-sm-3 control-label">Status</label>
                                    <div class="col-sm-9">
                                        <select class="form-control" id="categoriesStatus" name="categoriesStatus">
                                            <option value="1" <?php echo ($result['categories_active'] == "1") ? "selected" : ""; ?>>Available</option>
                                            <option value="2" <?php echo ($result['categories_active'] == "2") ? "selected" : ""; ?>>Not Available</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="update" id="updateCategoriesBtn" class="btn btn-primary btn-flat m-b-30 m-t-30">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php'); ?>

<script>
    $(document).ready(function() {
        const $groupNamesInput = $("#groupNames");
        const $autocompleteSuggestions = $("#autocompleteSuggestions");
        const $selectedItemsContainer = $("#selectedItems");
        const $groupIdsInput = $("#groupIds");

        let selectedGroups = <?php echo json_encode($linkedGroups); ?>;
        let availableGroups = <?php echo json_encode($availableGroups); ?>;

        // Render initial selected items
        updateSelectedItemsUI();

        // Display suggestions when user types
        $groupNamesInput.on("focus input", function() {
            const query = $(this).val().toLowerCase();
            const suggestions = availableGroups.filter(group => group.name.toLowerCase().includes(query));
            showSuggestions(suggestions);
        });

        // Show group suggestions
        function showSuggestions(groups) {
            $autocompleteSuggestions.empty().show();
            $.each(groups, function(_, group) {
                $("<div>")
                    .addClass("autocomplete-suggestion")
                    .text(group.name)
                    .data("id", group.id)
                    .on("click", function() {
                        addSelectedItem(group.id, group.name);
                    })
                    .appendTo($autocompleteSuggestions);
            });
        }
           // Add selected group to UI and update hidden input
           function addSelectedItem(groupId, groupName) {
            selectedGroups.push({ id: groupId, name: groupName });
            availableGroups = availableGroups.filter(group => group.id !== groupId);
            updateSelectedItemsUI();
            $groupNamesInput.val("");
            $autocompleteSuggestions.hide();
            updateGroupIdsInput();
        }
                // Update selected items UI
                function updateSelectedItemsUI() {
            $selectedItemsContainer.empty();
            $.each(selectedGroups, function(_, group) {
                const $item = $("<div>")
                    .addClass("selected-item")
                    .text(group.name)
                    .append(
                        $("<span>")
                            .addClass("remove-item")
                            .text(" x")
                            .on("click", function() {
                                removeSelectedItem(group.id);
                            })
                    );
                $item.appendTo($selectedItemsContainer);
            });
        }

        // Remove selected item and update available groups
        function removeSelectedItem(groupId) {
            const group = selectedGroups.find(g => g.id === groupId);
            if (group) {
                availableGroups.push(group); // Re-add to available groups
                selectedGroups = selectedGroups.filter(g => g.id !== groupId); // Remove from selected
                updateSelectedItemsUI();
                updateGroupIdsInput();
            }
        }

        // Update hidden input with selected group IDs
        function updateGroupIdsInput() {
            const groupIds = selectedGroups.map(group => group.id);
            $groupIdsInput.val(groupIds.join(","));
        }

        // Hide suggestions when clicking outside
        $(document).on("click", function(e) {
            if (!$(e.target).closest($groupNamesInput).length && !$(e.target).closest($autocompleteSuggestions).length) {
                $autocompleteSuggestions.hide();
            }
        });
    });
</script>
