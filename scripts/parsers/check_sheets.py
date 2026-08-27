import os
import openpyxl
import sys

base_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), '../..'))
excel_path = os.path.join(base_dir, 'database', 'data', 'ADASI_Mapping_JobPosition_Section_Dept.xlsx')
if not os.path.exists(excel_path):
    excel_path = 'ADASI_Mapping_JobPosition_Section_Dept.xlsx'

try:
    wb = openpyxl.load_workbook(excel_path, data_only=True)
    with open('sheet_names.txt', 'w') as f:
        f.write("\n".join(wb.sheetnames))
    print("Success")
except Exception as e:
    print(f"Error: {e}")
