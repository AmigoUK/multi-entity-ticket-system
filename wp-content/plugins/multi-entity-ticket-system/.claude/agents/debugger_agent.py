#!/usr/bin/env python3
"""
Debugging Agent for Qwen Code
"""

import json
import os
import re
import subprocess
import sys
import traceback
from pathlib import Path
from typing import Dict, List, Optional, Tuple

class DebuggerAgent:
    """Expert debugger specializing in root cause analysis."""
    
    def __init__(self):
        self.name = "debugger"
        self.description = "Debugging specialist for errors, test failures, and unexpected behavior"
        self.model = "qwen"
        
    def capture_error_context(self, error_output: str) -> Dict:
        """
        Capture error message and stack trace from error output.
        
        Args:
            error_output (str): Raw error output from system
            
        Returns:
            Dict: Parsed error context with message and stack trace
        """
        context = {
            'error_message': '',
            'error_type': '',
            'stack_trace': [],
            'file_paths': [],
            'line_numbers': [],
            'class_name': '',
            'method_name': '',
            'callback_function': ''
        }
        
        # Extract error message (usually first line)
        lines = error_output.strip().split('\n')
        if lines:
            context['error_message'] = lines[0]
            
        # Identify error type
        if 'TypeError' in error_output:
            context['error_type'] = 'TypeError'
        elif 'Parse error' in error_output:
            context['error_type'] = 'ParseError'
        elif 'Fatal error' in error_output:
            context['error_type'] = 'FatalError'
        elif 'call_user_func_array' in error_output:
            context['error_type'] = 'CallbackError'
            
        # Extract stack trace lines
        stack_lines = []
        collecting_stack_trace = False
        
        for line in lines:
            # Start collecting stack trace when we see "Stack trace:"
            if 'Stack trace:' in line:
                collecting_stack_trace = True
                continue
                
            # Stop collecting when we see "{main} thrown in"
            if '{main} thrown in' in line:
                collecting_stack_trace = False
                continue
                
            # Collect stack trace lines
            if collecting_stack_trace:
                stack_lines.append(line)
                
            # Extract file paths and line numbers
            file_match = re.search(r'in (\/[^:]+):(\d+)', line)
            if file_match:
                context['file_paths'].append(file_match.group(1))
                context['line_numbers'].append(int(file_match.group(2)))
                
            # Extract class and method names for WordPress callback errors
            # Extract class and method names for WordPress callback errors
            class_match = re.search(r"class ([A-Za-z0-9_]+) does not have a method '([^']*)'", line)
            if class_match:
                context['class_name'] = class_match.group(1)
                context['method_name'] = class_match.group(2)
                
            # Extract callback function for call_user_func_array errors
            callback_match = re.search(r'call_user_func_array\(\): Argument #1 \(\$callback\) must be a valid callback, (.+)', line)
            if callback_match:
                context['callback_function'] = callback_match.group(1)
                
        context['stack_trace'] = stack_lines
        return context
        
    def identify_reproduction_steps(self, error_context: Dict, recent_changes: List[str]) -> List[str]:
        """
        Identify reproduction steps from error context and recent changes.
        
        Args:
            error_context (Dict): Parsed error context
            recent_changes (List[str]): List of recently modified files
            
        Returns:
            List[str]: Reproduction steps
        """
        steps = []
        
        # Check if we can infer steps from file paths
        if error_context.get('file_paths'):
            for file_path in error_context['file_paths']:
                if file_path in recent_changes:
                    steps.append(f"Modified file: {file_path}")
                    
        # Add generic steps based on error type
        if 'syntax error' in error_context.get('error_message', '').lower():
            steps.append("Check PHP syntax with: php -l <file>")
        elif 'undefined function' in error_context.get('error_message', '').lower():
            steps.append("Verify function exists and is properly included")
        elif 'database connection' in error_context.get('error_message', '').lower():
            steps.append("Check database credentials in wp-config.php")
            
        return steps
        
    def isolate_failure_location(self, error_context: Dict) -> Tuple[str, int]:
        """
        Isolate the failure location from error context.
        
        Args:
            error_context (Dict): Parsed error context
            
        Returns:
            Tuple[str, int]: File path and line number of failure
        """
        # Get the most specific file path (usually the last one in stack trace)
        file_paths = error_context.get('file_paths', [])
        line_numbers = error_context.get('line_numbers', [])
        
        if file_paths and line_numbers:
            return file_paths[-1], line_numbers[-1]
        elif file_paths:
            return file_paths[-1], 1
        else:
            return 'unknown', 0
            
    def analyze_recent_changes(self, project_root: str) -> List[str]:
        """
        Analyze recent code changes using git.
        
        Args:
            project_root (str): Project root directory
            
        Returns:
            List[str]: List of recently modified files
        """
        try:
            # Change to project directory
            original_cwd = os.getcwd()
            os.chdir(project_root)
            
            # Get recently modified files (last 24 hours)
            result = subprocess.run([
                'git', 'log', '--name-only', '--pretty=format:', '--since=24.hours'
            ], capture_output=True, text=True, timeout=30)
            
            if result.returncode == 0:
                files = [f.strip() for f in result.stdout.split('\n') if f.strip()]
                return list(set(files))  # Remove duplicates
                
        except Exception as e:
            print(f"Warning: Could not analyze git history: {e}")
        finally:
            # Restore original directory
            os.chdir(original_cwd)
            
        return []
        
    def check_syntax_errors(self, file_path: str) -> Optional[str]:
        """
        Check PHP syntax errors in a file.
        
        Args:
            file_path (str): Path to PHP file
            
        Returns:
            Optional[str]: Syntax error message or None if no errors
        """
        try:
            # Use PHP to check syntax
            result = subprocess.run([
                'php', '-l', file_path
            ], capture_output=True, text=True, timeout=10)
            
            if result.returncode != 0:
                return result.stdout.strip()
        except Exception as e:
            return f"Could not check syntax: {e}"
            
        return None
        
    def form_hypotheses(self, error_context: Dict, recent_changes: List[str]) -> List[str]:
        """
        Form hypotheses about the root cause based on error context and recent changes.
        
        Args:
            error_context (Dict): Parsed error context
            recent_changes (List[str]): List of recently modified files
            
        Returns:
            List[str]: List of hypotheses about root cause
        """
        hypotheses = []
        error_message = error_context.get('error_message', '')
        error_type = error_context.get('error_type', '')
        
        # WordPress-specific hypotheses
        if 'WordPress' in error_message or 'wp-' in error_message:
            hypotheses.append("WordPress hook or filter callback not properly registered")
            hypotheses.append("Missing method in WordPress plugin class")
            hypotheses.append("Incorrect shortcode or widget registration")
            
        # Callback error hypotheses
        if 'call_user_func_array' in error_message:
            hypotheses.append("Invalid callback function or method reference")
            hypotheses.append("Class method does not exist")
            hypotheses.append("Incorrect function name in hook registration")
            
        # TypeError hypotheses
        if error_type == 'TypeError':
            hypotheses.append("Function or method call with incorrect arguments")
            hypotheses.append("Missing required parameters")
            hypotheses.append("Incorrect data types in function calls")
            
        # Parse error hypotheses
        if error_type == 'ParseError':
            hypotheses.append("PHP syntax error (missing semicolon, brace, etc.)")
            hypotheses.append("Incorrect function or class declaration")
            hypotheses.append("Malformed PHP code structure")
            
        # Fatal error hypotheses
        if error_type == 'FatalError':
            hypotheses.append("Uncaught exception or error")
            hypotheses.append("Missing required file or class")
            hypotheses.append("Memory limit exceeded")
            
        # Class method hypotheses
        class_name = error_context.get('class_name', '')
        method_name = error_context.get('method_name', '')
        if class_name and method_name:
            hypotheses.append(f"Class '{class_name}' is missing method '{method_name}'")
            hypotheses.append(f"Incorrect method name '{method_name}' in class '{class_name}'")
            hypotheses.append(f"Method '{method_name}' in class '{class_name}' has incorrect visibility")
            
        # Recent changes correlation
        if recent_changes:
            hypotheses.append("Issue caused by recent code changes")
            
        # File inclusion hypotheses
        if 'failed to open stream' in error_message.lower():
            hypotheses.append("Missing or inaccessible file")
            hypotheses.append("Incorrect file path")
            hypotheses.append("Insufficient file permissions")
            
        # Database connection hypotheses
        if 'database' in error_message.lower() or 'mysqli' in error_message.lower():
            hypotheses.append("Incorrect database credentials")
            hypotheses.append("Database server not running")
            hypotheses.append("MySQL socket path incorrect")
            
        # Undefined function/variable hypotheses
        if 'undefined' in error_message.lower():
            hypotheses.append("Function or variable not defined")
            hypotheses.append("Missing include/require statement")
            hypotheses.append("Typo in function/variable name")
            
        return hypotheses
        
    def add_debug_logging(self, file_path: str, line_number: int) -> bool:
        """
        Add strategic debug logging to a file.
        
        Args:
            file_path (str): Path to file
            line_number (int): Line number to add logging
            
        Returns:
            bool: True if logging was added successfully
        """
        try:
            # Read the file
            with open(file_path, 'r') as f:
                lines = f.readlines()
                
            # Insert debug logging before the specified line
            debug_line = f"// DEBUG: Reached line {line_number}\n"
            if line_number > 0 and line_number <= len(lines):
                lines.insert(line_number - 1, debug_line)
                
                # Write the file back
                with open(file_path, 'w') as f:
                    f.writelines(lines)
                    
                return True
                
        except Exception as e:
            print(f"Error adding debug logging: {e}")
            
        return False
        
    def inspect_variable_states(self, file_path: str, line_number: int) -> List[str]:
        """
        Inspect variable states around a line number.
        
        Args:
            file_path (str): Path to file
            line_number (int): Line number to inspect
            
        Returns:
            List[str]: List of variable inspection results
        """
        inspections = []
        
        try:
            with open(file_path, 'r') as f:
                lines = f.readlines()
                
            # Look at lines around the target line
            start = max(0, line_number - 3)
            end = min(len(lines), line_number + 3)
            
            for i in range(start, end):
                line = lines[i].strip()
                # Look for variable assignments
                if '=' in line and not line.startswith('//'):
                    inspections.append(f"Line {i+1}: {line}")
                    
        except Exception as e:
            inspections.append(f"Could not inspect variables: {e}")
            
        return inspections
        
    def generate_fix(self, error_context: Dict, root_cause: str) -> Dict:
        """
        Generate a specific code fix for the issue.
        
        Args:
            error_context (Dict): Parsed error context
            root_cause (str): Identified root cause
            
        Returns:
            Dict: Fix information including code and explanation
        """
        fix = {
            'explanation': '',
            'code_change': '',
            'file_path': '',
            'line_number': 0
        }
        
        error_message = error_context.get('error_message', '')
        error_type = error_context.get('error_type', '')
        class_name = error_context.get('class_name', '')
        method_name = error_context.get('method_name', '')
        
        # WordPress callback error fixes
        if 'call_user_func_array' in error_message and 'METS_Public' in error_message and 'register_shortcodes' in error_message:
            fix['explanation'] = f"The {class_name} class is missing the '{method_name}' method that WordPress is trying to call as a callback."
            fix['code_change'] = f'''// Option 1: Add the missing method to {class_name}
public function {method_name}() {{
    // Register shortcodes for the public-facing side of the site
    add_shortcode( 'ticket_form', array( $this, 'display_ticket_form' ) );
    add_shortcode( 'ticket_portal', array( $this, 'display_customer_portal' ) );
    add_shortcode( 'guest_ticket_access', array( $this, 'display_guest_ticket_access' ) );
}}

// Option 2: Remove incorrect hook registration if method shouldn't exist
// remove_action( 'init', array( $this, '{method_name}' ) );

// Option 3: Correct the hook registration if method name is wrong
// add_action( 'init', array( $this, 'correct_method_name' ) );'''
            fix['file_path'] = "public/class-mets-public.php"
            
        # Generic WordPress callback error fixes
        elif 'call_user_func_array' in error_message and class_name and method_name:
            fix['explanation'] = f"Add the missing '{method_name}' method to the '{class_name}' class or remove the incorrect hook registration."
            fix['code_change'] = f'''// Option 1: Add the missing method to {class_name}
public function {method_name}() {{
    // TODO: Implement the {method_name} method
    // This method was referenced as a WordPress callback but doesn't exist
}}

// Option 2: Remove incorrect hook registration if method shouldn't exist
// remove_action('init', array($instance, '{method_name}'));

// Option 3: Correct the hook registration if method name is wrong
// add_action('init', array($instance, 'correct_method_name'));'''
            fix['file_path'] = f"public/class-{class_name.lower().replace('_', '-')}.php"
            
        # Syntax error fixes
        elif 'syntax error' in error_message.lower():
            fix['explanation'] = "Fix PHP syntax errors by correcting missing semicolons, braces, or function declarations"
            fix['code_change'] = "// Correct the syntax error by adding missing semicolons or braces"
            
        # Undefined function fixes
        elif 'undefined function' in error_message.lower():
            fix['explanation'] = "Include the missing function definition or required file"
            fix['code_change'] = "// Add require_once statement for missing file"
            
        # Database connection fixes
        elif 'database' in error_message.lower() or 'mysqli' in error_message.lower():
            fix['explanation'] = "Correct database connection parameters"
            fix['code_change'] = "// Update DB_HOST in wp-config.php with correct socket path"
            
        # WordPress-specific fixes
        elif 'WordPress' in error_message:
            if 'hook' in error_message.lower() or 'filter' in error_message.lower():
                fix['explanation'] = "Correct WordPress hook or filter registration"
                fix['code_change'] = '''// Verify hook registration syntax
// Correct format: add_action('hook_name', 'function_name');
// Or for class methods: add_action('hook_name', array($instance, 'method_name'));

// Check that the function or method exists before registering'''
                fix['file_path'] = "includes/class-mets-core.php"
                
        return fix
        
    def generate_testing_approach(self, error_context: Dict) -> List[str]:
        """
        Generate testing approach for verifying the fix.
        
        Args:
            error_context (Dict): Parsed error context
            
        Returns:
            List[str]: Testing steps
        """
        tests = []
        error_message = error_context.get('error_message', '')
        error_type = error_context.get('error_type', '')
        class_name = error_context.get('class_name', '')
        method_name = error_context.get('method_name', '')
        
        # General testing approach
        tests.append("Verify the fix resolves the original error")
        tests.append("Test related functionality to ensure no regressions")
        tests.append("Run syntax check on modified files")
        
        # WordPress-specific testing
        if 'WordPress' in error_message or 'wp-' in error_message:
            tests.append("Test WordPress hook registration and execution")
            tests.append("Verify shortcode functionality works correctly")
            tests.append("Check widget integration if applicable")
            
        # Callback error testing
        if 'call_user_func_array' in error_message:
            tests.append("Verify callback function or method exists")
            tests.append("Test hook registration with correct callback")
            tests.append("Check function/method visibility and accessibility")
            
        # Class method testing
        if class_name and method_name:
            tests.append(f"Verify {class_name}::{method_name}() method exists and is accessible")
            tests.append(f"Test {class_name}::{method_name}() method functionality")
            tests.append("Check method signature and parameters")
            
        # Syntax error testing
        if error_type == 'ParseError':
            tests.append("Run php -l on all modified files")
            
        # Database connection testing
        if 'database' in error_message.lower() or 'mysqli' in error_message.lower():
            tests.append("Test database connection with wp-config.php credentials")
            
        # Undefined function testing
        if 'undefined' in error_message.lower():
            tests.append("Verify all required files are included")
            tests.append("Check function definitions exist")
            
        return tests
        
    def generate_prevention_recommendations(self, error_context: Dict) -> List[str]:
        """
        Generate prevention recommendations.
        
        Args:
            error_context (Dict): Parsed error context
            
        Returns:
            List[str]: Prevention recommendations
        """
        recommendations = []
        error_message = error_context.get('error_message', '')
        error_type = error_context.get('error_type', '')
        class_name = error_context.get('class_name', '')
        method_name = error_context.get('method_name', '')
        
        # General recommendations
        recommendations.append("Add syntax checking to pre-commit hooks")
        recommendations.append("Implement automated testing for critical paths")
        recommendations.append("Use version control with meaningful commit messages")
        
        # WordPress-specific recommendations
        if 'WordPress' in error_message or 'wp-' in error_message:
            recommendations.append("Configure IDE to highlight WordPress coding standards")
            recommendations.append("Add WordPress coding standards checking to CI pipeline")
            recommendations.append("Use WordPress plugin boilerplate for consistent structure")
            
        # Callback error recommendations
        if 'call_user_func_array' in error_message:
            recommendations.append("Use static analysis tools to detect missing methods")
            recommendations.append("Implement proper class interface definitions")
            recommendations.append("Add unit tests for class method existence")
            recommendations.append("Verify callback functions exist before registering hooks")
            
        # Syntax error recommendations
        if error_type == 'ParseError':
            recommendations.append("Configure IDE to highlight syntax errors in real-time")
            recommendations.append("Add PHP syntax checking to CI pipeline")
            
        # Database connection recommendations
        if 'database' in error_message.lower() or 'mysqli' in error_message.lower():
            recommendations.append("Validate database connections during deployment")
            recommendations.append("Use environment-specific configuration files")
            
        # Undefined function recommendations
        if 'undefined' in error_message.lower():
            recommendations.append("Implement proper autoloading or include checks")
            recommendations.append("Use static analysis tools to detect undefined symbols")
            
        # Class method recommendations
        if class_name and method_name:
            recommendations.append("Use PHPDoc annotations for method documentation")
            recommendations.append("Implement proper method visibility controls")
            recommendations.append("Add method existence checks before hook registration")
            
        return recommendations
        
    def debug_issue(self, error_output: str, project_root: str = '.') -> Dict:
        """
        Main debugging method that orchestrates the entire process.
        
        Args:
            error_output (str): Raw error output from system
            project_root (str): Project root directory
            
        Returns:
            Dict: Complete debugging analysis
        """
        analysis = {
            'error_context': {},
            'reproduction_steps': [],
            'failure_location': ('', 0),
            'recent_changes': [],
            'hypotheses': [],
            'root_cause': '',
            'evidence': [],
            'fix': {},
            'testing_approach': [],
            'prevention_recommendations': [],
            'success': False
        }
        
        try:
            # 1. Capture error context
            analysis['error_context'] = self.capture_error_context(error_output)
            
            # 2. Analyze recent changes
            analysis['recent_changes'] = self.analyze_recent_changes(project_root)
            
            # 3. Identify reproduction steps
            analysis['reproduction_steps'] = self.identify_reproduction_steps(
                analysis['error_context'], 
                analysis['recent_changes']
            )
            
            # 4. Isolate failure location
            analysis['failure_location'] = self.isolate_failure_location(analysis['error_context'])
            
            # 5. Form hypotheses
            analysis['hypotheses'] = self.form_hypotheses(
                analysis['error_context'], 
                analysis['recent_changes']
            )
            
            # 6. Determine root cause (simple heuristic for now)
            if analysis['hypotheses']:
                analysis['root_cause'] = analysis['hypotheses'][0]
                
            # 7. Generate evidence (simplified)
            analysis['evidence'] = [
                f"Error message: {analysis['error_context'].get('error_message', '')}",
                f"Failure location: {analysis['failure_location'][0]}:{analysis['failure_location'][1]}"
            ]
            
            # 8. Generate fix
            analysis['fix'] = self.generate_fix(analysis['error_context'], analysis['root_cause'])
            
            # 9. Generate testing approach
            analysis['testing_approach'] = self.generate_testing_approach(analysis['error_context'])
            
            # 10. Generate prevention recommendations
            analysis['prevention_recommendations'] = self.generate_prevention_recommendations(analysis['error_context'])
            
            analysis['success'] = True
            
        except Exception as e:
            analysis['error'] = str(e)
            analysis['traceback'] = traceback.format_exc()
            
        return analysis
        
    def format_analysis_report(self, analysis: Dict) -> str:
        """
        Format the debugging analysis into a human-readable report.
        
        Args:
            analysis (Dict): Complete debugging analysis
            
        Returns:
            str: Formatted report
        """
        if not analysis.get('success', False):
            return f"Debugging failed: {analysis.get('error', 'Unknown error')}"
            
        report = []
        report.append("# Debugging Analysis Report")
        report.append("")
        
        # Error Context
        report.append("## Error Context")
        report.append(f"- **Error Message**: {analysis['error_context'].get('error_message', 'N/A')}")
        report.append("")
        
        # Root Cause
        report.append("## Root Cause")
        report.append(f"{analysis.get('root_cause', 'Not determined')}")
        report.append("")
        
        # Evidence
        report.append("## Evidence")
        for evidence in analysis.get('evidence', []):
            report.append(f"- {evidence}")
        report.append("")
        
        # Fix
        report.append("## Proposed Fix")
        report.append(f"**Explanation**: {analysis['fix'].get('explanation', 'N/A')}")
        if analysis['fix'].get('code_change'):
            report.append(f"**Code Change**: {analysis['fix'].get('code_change')}")
        report.append("")
        
        # Testing Approach
        report.append("## Testing Approach")
        for test in analysis.get('testing_approach', []):
            report.append(f"- {test}")
        report.append("")
        
        # Prevention Recommendations
        report.append("## Prevention Recommendations")
        for recommendation in analysis.get('prevention_recommendations', []):
            report.append(f"- {recommendation}")
        report.append("")
        
        return "\n".join(report)

def main():
    """Main entry point for the debugger agent."""
    if len(sys.argv) < 2:
        print("Usage: python debugger_agent.py <error_output_file> [project_root]")
        sys.exit(1)
        
    error_file = sys.argv[1]
    project_root = sys.argv[2] if len(sys.argv) > 2 else '.'
    
    # Read error output
    try:
        with open(error_file, 'r') as f:
            error_output = f.read()
    except Exception as e:
        print(f"Error reading error file: {e}")
        sys.exit(1)
        
    # Create debugger agent
    debugger = DebuggerAgent()
    
    # Debug the issue
    analysis = debugger.debug_issue(error_output, project_root)
    
    # Print formatted report
    report = debugger.format_analysis_report(analysis)
    print(report)
    
    # Also save to file
    import time
    report_file = f"debug_analysis_{int(time.time())}.md"
    with open(report_file, 'w') as f:
        f.write(report)
        
    print(f"\nFull report saved to: {report_file}")

if __name__ == "__main__":
    main()