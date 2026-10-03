<?php
include '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $course_name = $_POST['course_name'];
    $semester = $_POST['semester'];
    $academic_year = $_POST['academic_year'];
    
    $first_year_male = (int)$_POST['first_year_male'];
    $first_year_female = (int)$_POST['first_year_female'];
    $second_year_male = (int)$_POST['second_year_male'];
    $second_year_female = (int)$_POST['second_year_female'];
    $third_year_male = (int)$_POST['third_year_male'];
    $third_year_female = (int)$_POST['third_year_female'];
    $fourth_year_male = (int)$_POST['fourth_year_male'];
    $fourth_year_female = (int)$_POST['fourth_year_female'];
    
    // Calculate totals
    $male_students = $first_year_male + $second_year_male + $third_year_male + $fourth_year_male;
    $female_students = $first_year_female + $second_year_female + $third_year_female + $fourth_year_female;
    $total_students = $male_students + $female_students;
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO courses (course_name, semester, academic_year, male_students, female_students, total_students, 
                            first_year_male, first_year_female, second_year_male, second_year_female, 
                            third_year_male, third_year_female, fourth_year_male, fourth_year_female) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiiiiiiiiiii", $course_name, $semester, $academic_year, $male_students, $female_students, $total_students,
                      $first_year_male, $first_year_female, $second_year_male, $second_year_female,
                      $third_year_male, $third_year_female, $fourth_year_male, $fourth_year_female);
    
    if ($stmt->execute()) {
        echo "<p class='text-green-500'>Data inserted successfully!</p>";
    } else {
        echo "<p class='text-red-500'>Error: " . $stmt->error . "</p>";
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Course Data</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6">Input Course Data</h1>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Course Name</label>
                <input type="text" name="course_name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Semester</label>
                <select name="semester" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2">
                    <option value="1st Semester">1st Semester</option>
                    <option value="2nd Semester">2nd Semester</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Academic Year</label>
                <select name="academic_year" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2">
                    <option value="">Select Academic Year</option>
                    <option value="2025-2026">2025-2026</option>
                </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">1st Year Male</label>
                    <input type="number" name="first_year_male" min="0" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">1st Year Female</label>
                    <input type="number" name="first_year_female" min="0" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">2nd Year Male</label>
                    <input type="number" name="second_year_male" min="0" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">2nd Year Female</label>
                    <input type="number" name="second_year_female" min="0" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">3rd Year Male</label>
                    <input type="number" name="third_year_male" min="0" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">3rd Year Female</label>
                    <input type="number" name="third_year_female" min="0" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">4th Year Male</label>
                    <input type="number" name="fourth_year_male" min="0" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">4th Year Female</label>
                    <input type="number" name="fourth_year_female" min="0" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2">
                </div>
            </div>
            
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Submit</button>
        </form>
    </div>
</body>
</html>