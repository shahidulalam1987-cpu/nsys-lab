# NSYS Lab Employee Department - Phase 1 Final Requirements

## Project Goal

NSYS Lab currently has:

* Client Department
* Payment System
* Invoice System
* Statement Ledger System

Now build Employee Department Phase 1.

Important:

This is NOT a generic HR software.

This is an employee management system for NSYS Agency where employees are assigned to clients and salary is managed through NSYS Agency.

Agency may reassign employees between clients at any time.

Client pays salary funds to NSYS Agency.

NSYS Agency pays employee salaries.

Agency does not earn profit from employee salary management.

---

# User Roles

## Admin

NSYS Agency Management

Full access.

## Client

Can view assigned employees and salary-related information.

## Employee

Can view own dashboard and salary information.

---

# Employee Lifecycle

Employee Status:

* Probation
* Active
* On Leave
* Suspended
* Terminated

Probation Rule:

* Employee joins
* Automatically enters Probation
* Probation period = 7 calendar days
* After 7 days employee becomes Eligible For Confirmation
* Admin manually confirms

Store:

* Joining Date
* Confirmation Date
* Last Working Date

---

# Employee Master

Fields:

* Employee ID
* Name
* Mobile
* Department
* Role
* Joining Date
* Status
* Salary Type
* Monthly Salary
* Bank / Mobile Banking Information

Do not add unnecessary HR fields.

---

# Client Assignment System

Admin can assign any employee to any client.

Assignment fields:

* Employee
* Client
* Assigned From
* Assigned To
* Status
* Notes

Admin may transfer employees between clients at any time.

Assignment history must be preserved.

---

# Salary Rules

Salary is not counted automatically for all assigned days.

Salary is counted only when work is active.

Non-countable conditions:

* Boosting OFF
* Client Issue
* Business Closed
* Work Stopped
* Agency Hold

For salary calculation:

Counted Days
Non-Counted Days

must be tracked.

---

# Salary Calculation

Monthly Salary Model

Example:

Monthly Salary = 9000

If employee worked only 20 counted days:

Salary = 9000 / month days × counted days

System must calculate automatically.

---

# Client Salary Fund

Client pays salary funds to NSYS Agency.

Track:

* Required Salary
* Client Paid
* Current Due
* Available Balance

Example:

Required Salary = 25000
Paid = 20000
Due = 5000

---

# Salary Payment Submission

Separate from boosting payments.

Client submits:

* Amount
* Method
* Transaction ID
* Screenshot
* Note

Status:

* Pending
* Approved
* Rejected

Admin approves or rejects.

Approved payments increase Salary Fund Balance.

---

# Client Dashboard

Client can view:

## Assigned Employees

* Employee Name
* Role
* Status
* Joining Date
* Confirmation Date
* Salary

## Salary Fund Summary

* Total Salary Required
* Paid to NSYS
* Due
* Available Balance

## Salary Payment History

* Date
* Amount
* Status

---

# Employee Dashboard

Employee can view:

* My Profile
* My Assigned Client
* Status
* Joining Date
* Confirmation Status
* Monthly Salary
* Counted Days
* Non-Counted Days
* Salary Status

Employee cannot view:

* Client finances
* Other employee data
* Agency internal data

---

# Phase 1 Scope

Build only:

1. Employee Master
2. Client Assignment
3. Salary Count Logic
4. Salary Fund Management
5. Salary Payment Submission
6. Employee Dashboard
7. Client Employee View

Do NOT build:

* Attendance System
* Ticket System
* Messaging System
* AI Features
* Advanced Training Module
* Performance Scoring

Those will be Phase 2 or later.

---

# Development Rule

Before coding:

Analyze existing NSYS Lab architecture.

Reuse:

* User roles
* Ledger concepts
* Payment approval workflow
* Dashboard structure

Do not create duplicate systems.

Keep implementation scalable for future growth to 100,000 employees.
