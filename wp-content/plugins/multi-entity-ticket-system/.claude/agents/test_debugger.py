#!/usr/bin/env python3
"""
Test script for the Debugger Agent
"""

import os
import sys
import tempfile

# Add the agents directory to Python path
sys.path.append(os.path.join(os.path.dirname(__file__), '.claude', 'agents'))

from debugger_agent import DebuggerAgent

def test_syntax_error_debugging():
    """Test debugging a PHP syntax error."""
    
    # Sample PHP syntax error output
    error_output = '''
Parse error: syntax error, unexpected token "public" in /Users/tomaszlewandowski/Library/CloudStorage/Dropbox/coding/QwenCodeTest/dev/wp-content/plugins/multi-entity-ticket-system/public/class-mets-public.php on line 129
Errors parsing /Users/tomaszlewandowski/Library/CloudStorage/Dropbox/coding/QwenCodeTest/dev/wp-content/plugins/multi-entity-ticket-system/public/class-mets-public.php
'''
    
    # Create debugger agent
    debugger = DebuggerAgent()
    
    # Debug the issue
    analysis = debugger.debug_issue(error_output, '.')
    
    # Print formatted report
    report = debugger.format_analysis_report(analysis)
    print("=== Syntax Error Debugging Test ===")
    print(report)
    print("\n" + "="*50 + "\n")

def test_database_error_debugging():
    """Test debugging a database connection error."""
    
    # Sample database error output
    error_output = '''
Warning: mysqli_real_connect(): (HY000/2002): No such file or directory in /Users/tomaszlewandowski/Library/CloudStorage/Dropbox/coding/QwenCodeTest/dev/wp-includes/class-wpdb.php on line 1988
Error establishing a database connection
'''
    
    # Create debugger agent
    debugger = DebuggerAgent()
    
    # Debug the issue
    analysis = debugger.debug_issue(error_output, '.')
    
    # Print formatted report
    report = debugger.format_analysis_report(analysis)
    print("=== Database Error Debugging Test ===")
    print(report)
    print("\n" + "="*50 + "\n")

def test_undefined_function_debugging():
    """Test debugging an undefined function error."""
    
    # Sample undefined function error output
    error_output = '''
Fatal error: Uncaught Error: Call to undefined method METS_Guest_Access_Token_Model::create() in /Users/tomaszlewandowski/Library/CloudStorage/Dropbox/coding/QwenCodeTest/dev/wp-content/plugins/multi-entity-ticket-system/public/class-mets-guest-ticket-access.php on line 282
'''
    
    # Create debugger agent
    debugger = DebuggerAgent()
    
    # Debug the issue
    analysis = debugger.debug_issue(error_output, '.')
    
    # Print formatted report
    report = debugger.format_analysis_report(analysis)
    print("=== Undefined Function Error Debugging Test ===")
    print(report)
    print("\n" + "="*50 + "\n")

def main():
    """Run all tests."""
    print("Running Debugger Agent Tests...\n")
    
    test_syntax_error_debugging()
    test_database_error_debugging()
    test_undefined_function_debugging()
    
    print("All tests completed!")

if __name__ == "__main__":
    main()