#include <stdio.h>

// Function to perform Bubble Sort
void bubbleSort(int arr[], int n) {
    int i, j, temp;
    int swapped; // to optimize if array becomes sorted early

    // Outer loop for number of passes
    for (i = 0; i < n - 1; i++) {
        swapped = 0; // reset swapped flag before each pass

        // Inner loop for pairwise comparison
        for (j = 0; j < n - i - 1; j++) {
            // Compare adjacent elements
            if (arr[j] > arr[j + 1]) {
                // Swap if elements are out of order
                temp = arr[j];
                arr[j] = arr[j + 1];
                arr[j + 1] = temp;
                swapped = 1; // set flag indicating swap happened
            }
        }

        // If no swaps in this pass → array is sorted
        if (swapped == 0)
            break;
    }
}

// Function to print the array
void printArray(int arr[], int n) {
    int i;
    for (i = 0; i < n; i++)
        printf("%d ", arr[i]);
    printf("\n");
}

// Main function
int main() {
    int arr[100], n, i;

    printf("Enter the number of elements: ");
    scanf("%d", &n);

    printf("Enter %d elements:\n", n);
    for (i = 0; i < n; i++)
        scanf("%d", &arr[i]);

    printf("\nUnsorted array: ");
    printArray(arr, n);

    bubbleSort(arr, n);

    printf("\nSorted array in ascending order: ");
    printArray(arr, n);

    return 0;
}