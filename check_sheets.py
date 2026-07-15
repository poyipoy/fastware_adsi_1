import openpyxl
import sys

try:
    wb = openpyxl.load_workbook('ADASI_Mapping_JobPosition_Section_Dept.xlsx', data_only=True)
    with open('sheet_names.txt', 'w') as f:
        f.write("\n".join(wb.sheetnames))
    print("Success")
except Exception as e:
    print(f"Error: {e}")
